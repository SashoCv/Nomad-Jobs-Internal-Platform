<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\CompanyRequest;
use App\Models\ContractCandidate;
use App\Models\User;
use App\Models\UserOwner;
use App\Models\Role;
use App\Models\AssignedJob;
use App\Models\ChangeLog;
use App\Notifications\CompanyJobCreatedNotification;
use App\Repository\NotificationRepository;
use App\Repository\SendEmailRepositoryForCreateCompanyJob;
use App\Repository\UsersNotificationRepository;
use App\Traits\HasRolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class CompanyJobController extends Controller
{
    use HasRolePermissions;

    protected SendEmailRepositoryForCreateCompanyJob $sendEmailRepositoryForCreateCompanyJob;

    public function __construct(SendEmailRepositoryForCreateCompanyJob $sendEmailRepositoryForCreateCompanyJob)
    {
        $this->sendEmailRepositoryForCreateCompanyJob = $sendEmailRepositoryForCreateCompanyJob;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;
        $contractType = $request->contract_type;
        $statusFilter = $request->status; // Can be comma-separated: "pending,active"

        $query = DB::table('company_jobs')
            ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
            ->leftJoin('agent_candidates', function($join) {
                $join->on('agent_candidates.company_job_id', '=', 'company_jobs.id');
            })
            ->select(
                'company_jobs.id',
                'companies.logoPath',
                'companies.companyCity',
                'company_jobs.company_id',
                'company_jobs.job_title',
                'company_jobs.real_position',
                'company_jobs.number_of_positions',
                'company_jobs.contract_type',
                'company_jobs.job_description',
                'company_jobs.status',
                'company_jobs.approved_at',
                'company_jobs.published_at',
                'company_jobs.pending_revision',
                'company_jobs.revision_requested_by',
                'company_jobs.revision_requested_at',
                'companies.nameOfCompany',
                'company_jobs.created_at',
                'company_jobs.updated_at',
                'company_jobs.deleted_at',
                DB::raw("COUNT(agent_candidates.id) as candidates_count"),
                DB::raw("SUM(CASE WHEN agent_candidates.status_for_candidate_from_agent_id = 3 THEN 1 ELSE 0 END) as approved_count")
            )
            ->whereNull('company_jobs.deleted_at')
            ->groupBy(
                'company_jobs.id',
                'companies.logoPath',
                'companies.companyCity',
                'company_jobs.company_id',
                'company_jobs.job_title',
                'company_jobs.real_position',
                'company_jobs.number_of_positions',
                'company_jobs.contract_type',
                'company_jobs.job_description',
                'company_jobs.status',
                'company_jobs.approved_at',
                'company_jobs.published_at',
                'company_jobs.pending_revision',
                'company_jobs.revision_requested_by',
                'company_jobs.revision_requested_at',
                'companies.nameOfCompany',
                'company_jobs.created_at',
                'company_jobs.updated_at',
                'company_jobs.deleted_at'
            )
            ->orderBy('company_jobs.created_at', 'desc');

        if ($contractType) {
            $contractTypeName = ContractCandidate::where('id', $contractType)->value('name');
            $query->where('company_jobs.contract_type', $contractTypeName);
        }

        // Filter by status (supports comma-separated values)
        if ($statusFilter) {
            $statuses = explode(',', $statusFilter);
            $query->whereIn('company_jobs.status', $statuses);
        }

        switch ($roleId) {
            case Role::GENERAL_MANAGER:
            case Role::MANAGER:
            case Role::OFFICE:
            case Role::HR:
            case Role::OFFICE_MANAGER:
            case Role::RECRUITERS:
            case Role::FINANCE:
                if ($companyId = $request->company_id) {
                    $query->where('companies.id', $companyId);
                }
                break;

            case Role::COMPANY_USER:
                $query->where('company_jobs.company_id', $user->company_id);
                break;

            case Role::COMPANY_OWNER:
                $companyIds = UserOwner::where('user_id', $user->id)
                    ->pluck('company_id')
                    ->toArray();
                $query->whereIn('company_jobs.company_id', $companyIds);
                break;

            case Role::AGENT:
                $companyJobIds = AssignedJob::where('user_id', $user->id)
                    ->pluck('company_job_id')
                    ->toArray();
                $query->whereIn('company_jobs.id', $companyJobIds);
                break;
        }


        $allJobPostings = $query->paginate();

        // Load request data and change_logs for each job posting
        $jobIds = collect($allJobPostings->items())->pluck('id')->toArray();

        // Load requests
        $requests = CompanyRequest::whereIn('company_job_id', $jobIds)
            ->get()
            ->keyBy('company_job_id');

        // Load change logs (linked to company_jobs table via record_id)
        $changeLogs = ChangeLog::with('user')
            ->where('tableName', 'company_jobs')
            ->whereIn('record_id', $jobIds)
            ->get()
            ->groupBy('record_id');

        // Get user info for created_by
        $companyJobs = CompanyJob::whereIn('id', $jobIds)->with('user')->get()->keyBy('id');

        // Transform the data to include request info
        $transformedData = collect($allJobPostings->items())->map(function ($job) use ($requests, $changeLogs, $companyJobs) {
            $jobObj = (object) $job;
            $request = $requests->get($job->id);
            $companyJob = $companyJobs->get($job->id);
            $jobChangeLogs = $changeLogs->get($job->id) ?? collect([]);

            $jobObj->request = $request ? [
                'id' => $request->id,
                'approved' => $request->approved,
                'description' => $request->description,
                'created_at' => $request->created_at,
            ] : null;

            // Add change_logs separately (they're linked to the job, not the request)
            $jobObj->change_logs = $jobChangeLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'field_name' => $log->fieldName,
                    'old_value' => $log->oldValue,
                    'new_value' => $log->newValue,
                    'status' => $log->status ?? 'pending',
                    'is_applied' => $log->isApplied,
                    'created_at' => $log->created_at,
                    'created_by' => $log->user ? [
                        'id' => $log->user->id,
                        'full_name' => trim($log->user->firstName . ' ' . $log->user->lastName),
                        'email' => $log->user->email,
                    ] : null,
                ];
            })->values()->toArray();

            // Add created_by from the user who created the job posting
            $jobObj->created_by = $companyJob && $companyJob->user ? [
                'id' => $companyJob->user->id,
                'full_name' => trim($companyJob->user->firstName . ' ' . $companyJob->user->lastName),
                'email' => $companyJob->user->email,
            ] : null;

            // Decode pending_revision JSON (since it comes from raw query, not Eloquent)
            if (isset($jobObj->pending_revision) && is_string($jobObj->pending_revision)) {
                $jobObj->pending_revision = json_decode($jobObj->pending_revision, true);
            }

            return $jobObj;
        });

        // Replace the items in the paginator with transformed data
        $allJobPostings->setCollection($transformedData);

        return response()->json([
            "status" => "success",
            "message" => "Job retrieved successfully",
            "data" => $allJobPostings
        ], 200);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($this->checkPermission(Permission::JOB_POSTINGS_CREATE)) {
            $companyJob = new CompanyJob();

            $companyJob->user_id = $user->id;
            // Set company_id based on user role - company users can only create for their company
            if ($user->hasRole(Role::COMPANY_USER)) {
                $companyJob->company_id = $user->company_id;
            } else {
                $companyJob->company_id = $request->company_id;
            }
            // Set job_title from selected position
            if ($request->position_id) {
                $position = \App\Models\Position::find($request->position_id);
                $companyJob->job_title = $position ? $position->jobPosition : $request->job_title;
            } else {
                $companyJob->job_title = $request->job_title;
            }
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->contract_type = $request->contract_type;
            $companyJob->requirementsForCandidates = $request->requirementsForCandidates;
            $companyJob->salary = $request->salary;
            $companyJob->bonus = $request->bonus;
            $companyJob->workTime = $request->workTime;
            $companyJob->additionalWork = $request->additionalWork;
            $companyJob->vacationDays = $request->vacationDays;
            $companyJob->rent = $request->rent;
            $companyJob->food = $request->food;
            $companyJob->otherDescription = $request->otherDescription;
            $companyJob->countryOfOrigin = $request->countryOfOrigin; // Assuming this field is added in the migration
            $companyJob->position_id = $request->position_id; // Job position selection
            $companyJob->real_position = $request->real_position; // Real position text
            $companyJob->country_id = $request->country_id; // Country foreign key

            // Set initial status based on user role
            if ($user->hasRole(Role::COMPANY_USER) || $user->hasRole(Role::COMPANY_OWNER)) {
                // Company users create pending job postings that need approval
                $companyJob->status = CompanyJob::STATUS_PENDING;
            } else {
                // Staff/Admin can create active job postings directly
                $companyJob->status = CompanyJob::STATUS_ACTIVE;
                $companyJob->approved_at = now();
                $companyJob->approved_by = $user->id;
                $companyJob->published_at = now();
            }

            if ($companyJob->save()) {
                $companyName = Company::where('id', $companyJob->company_id)->first();
                $companyForThisJob = $companyName->nameOfCompany;

                // Create Request
                $companyRequest = new CompanyRequest();
                $companyRequest->company_job_id = $companyJob->id;
                $companyRequest->approved = false; // Automatically approve for staff
                $companyRequest->description = "Job created by " . $user->firstName . " " . $user->lastName;
                $companyRequest->save();

                $notificationMessages = [
                    'message' => $companyForThisJob . ' created new job posting: ' . $request->job_title,
                    'type' => 'job_posting'
                ];

                $notification_id = NotificationRepository::createNotification($notificationMessages);
                UsersNotificationRepository::createNotificationForUsers($notification_id);

                $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);

                if ($this->isStaff() && $request->agentsIds) {
                    foreach ($request->agentsIds as $agentId) {
                        AssignedJob::create([
                            'user_id' => $agentId,
                            'company_job_id' => $companyJob->id
                        ]);
                    }
                }

                return response()->json([
                    "status" => "success",
                    "message" => "Job created successfully",
                    "data" => $companyJob,
                ], 200);
            } else {
                return response()->json(['message' => 'Job creation failed'], 400);
            }
        }

        return response()->json([
            "status" => "error",
            "message" => "Unauthorized access",
        ], 403);
    }

    public function show($id)
    {
        $user = Auth::user();

        if ($this->checkPermission(Permission::JOB_POSTINGS_READ)) {
            $companyJob = CompanyJob::where('id', $id)->first();

            $candidates_count = DB::table('agent_candidates')
                ->where('company_job_id', $id)
                ->where('status_for_candidate_from_agent_id', 3)
                ->count();

            $companyJob->candidates_count = $candidates_count;
            if (!$companyJob) {
                return response()->json([
                    "status" => "error",
                    "message" => "Job not found",
                ], 404);
            }

            $company = Company::where('id', $companyJob->company_id)->first();
            $companyJob->companyImage = $company->logoPath;
            $companyJob->companyCity = $company->companyCity;
            $companyJob->companyName = $company->nameOfCompany;

            // Get position NKDP if position_id exists
            if ($companyJob->position_id) {
                $position = \App\Models\Position::find($companyJob->position_id);
                $companyJob->position_nkdp = $position ? $position->NKDP : null;
            }

            if ($this->isStaff()) {
                $assignedJobs = AssignedJob::where('company_job_id', $id)->get();
                $companyJob->agentsIds = $assignedJobs->pluck('user_id')->toArray();
            }

            // Load change logs for this job posting
            $changeLogs = ChangeLog::with('user')
                ->where('tableName', 'company_jobs')
                ->where('record_id', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'field_name' => $log->fieldName,
                        'old_value' => $log->oldValue,
                        'new_value' => $log->newValue,
                        'status' => $log->status ?? 'pending',
                        'is_applied' => $log->isApplied,
                        'created_at' => $log->created_at,
                        'created_by' => $log->user ? [
                            'id' => $log->user->id,
                            'full_name' => trim($log->user->firstName . ' ' . $log->user->lastName),
                            'email' => $log->user->email,
                        ] : null,
                    ];
                });
            $companyJob->change_logs = $changeLogs;

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        }

        return response()->json([
            "status" => "error",
            "message" => "Unauthorized access",
        ], 403);
    }

    public function edit(CompanyJob $companyJob)
    {
        //
    }

    public function update(Request $request, $companyJobId)
    {
        $user = Auth::user();

        if ($this->checkPermission(Permission::JOB_POSTINGS_UPDATE)) {
            $companyJob = CompanyJob::find($companyJobId);

            if (!$companyJob) {
                return response()->json([
                    "status" => "error",
                    "message" => "Job not found",
                ], 404);
            }

            // Company users can only edit their own company's job postings
            if ($user->hasRole(Role::COMPANY_USER) && $companyJob->company_id !== $user->company_id) {
                return response()->json([
                    "status" => "error",
                    "message" => "You can only edit job postings from your own company",
                ], 403);
            }

            // Company owners can only edit job postings from companies they own
            if ($user->hasRole(Role::COMPANY_OWNER)) {
                $ownedCompanyIds = UserOwner::where('user_id', $user->id)->pluck('company_id')->toArray();
                if (!in_array($companyJob->company_id, $ownedCompanyIds)) {
                    return response()->json([
                        "status" => "error",
                        "message" => "You can only edit job postings from companies you own",
                    ], 403);
                }
            }

            // Check if there's already a pending revision - can't edit while one is pending
            if ($companyJob->isRevisionRequested()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Cannot edit while a revision is pending approval",
                ], 409);
            }

            // Company users editing active/filled jobs must submit a revision request
            $isCompanyUser = $user->hasRole(Role::COMPANY_USER) || $user->hasRole(Role::COMPANY_OWNER);

            if ($isCompanyUser && $companyJob->canRequestRevision()) {
                return $this->submitRevisionRequest($request, $companyJob, $user);
            }

            // Direct update for staff OR non-active jobs (pending/inactive/rejected)
            return $this->performDirectUpdate($request, $companyJob, $user);
        }

        return response()->json([
            "status" => "error",
            "message" => "Unauthorized access",
        ], 403);
    }

    /**
     * Create change logs for company users editing active/filled job postings
     */
    private function createChangeLogsForUpdate(Request $request, CompanyJob $companyJob, $user)
    {
        // Fields that can be changed via change logs
        $trackableFields = [
            'job_title', 'number_of_positions', 'job_description', 'contract_type',
            'requirementsForCandidates', 'salary', 'bonus', 'workTime',
            'additionalWork', 'vacationDays', 'rent', 'food', 'otherDescription'
        ];

        $changeLogsCreated = [];

        // Determine new job_title based on position_id
        $newJobTitle = $request->job_title;
        if ($request->position_id) {
            $position = \App\Models\Position::find($request->position_id);
            $newJobTitle = $position ? $position->jobPosition : $request->job_title;
        }

        // Build array of new values
        $newValues = [
            'job_title' => $newJobTitle,
            'number_of_positions' => $request->number_of_positions,
            'job_description' => $request->job_description,
            'contract_type' => $request->contract_type,
            'requirementsForCandidates' => $request->requirementsForCandidates,
            'salary' => $request->salary,
            'bonus' => $request->bonus,
            'workTime' => $request->workTime,
            'additionalWork' => $request->additionalWork,
            'vacationDays' => $request->vacationDays,
            'rent' => $request->rent,
            'food' => $request->food,
            'otherDescription' => $request->otherDescription,
        ];

        foreach ($trackableFields as $field) {
            $oldValue = (string) ($companyJob->{$field} ?? '');
            $newValue = (string) ($newValues[$field] ?? '');

            // Only create change log if value actually changed
            if ($oldValue !== $newValue) {
                $changeLog = ChangeLog::create([
                    'tableName' => 'company_jobs',
                    'record_id' => $companyJob->id,
                    'fieldName' => $field,
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                    'user_id' => $user->id,
                    'company_id' => $companyJob->company_id,
                    'status' => 'pending',
                    'isApplied' => false,
                ]);
                $changeLogsCreated[] = $changeLog;
            }
        }

        if (empty($changeLogsCreated)) {
            return response()->json([
                "status" => "success",
                "message" => "No changes detected",
                "data" => $companyJob,
                "changes_pending_approval" => false
            ], 200);
        }

        // Create notification for staff about pending changes
        $companyName = Company::where('id', $companyJob->company_id)->first()->nameOfCompany;
        $notificationData = [
            'message' => $companyName . ' submitted changes for approval on job posting: ' . $companyJob->job_title,
            'type' => 'job_posting_change_request'
        ];
        $notification = NotificationRepository::createNotification($notificationData);
        UsersNotificationRepository::createNotificationForUsers($notification);

        return response()->json([
            "status" => "success",
            "message" => "Changes submitted for approval",
            "data" => $companyJob,
            "changes_pending_approval" => true,
            "change_logs_count" => count($changeLogsCreated)
        ], 200);
    }

    /**
     * Perform direct update (for staff users or non-active job postings)
     */
    private function performDirectUpdate(Request $request, CompanyJob $companyJob, $user)
    {
        // Set job_title from selected position
        if ($request->position_id) {
            $position = \App\Models\Position::find($request->position_id);
            $companyJob->job_title = $position ? $position->jobPosition : $request->job_title;
        } else {
            $companyJob->job_title = $request->job_title;
        }

        $companyJob->number_of_positions = $request->number_of_positions;
        $companyJob->job_description = $request->job_description;
        $companyJob->contract_type = $request->contract_type;
        $companyJob->requirementsForCandidates = $request->requirementsForCandidates;
        $companyJob->salary = $request->salary;
        $companyJob->bonus = $request->bonus;
        $companyJob->workTime = $request->workTime;
        $companyJob->additionalWork = $request->additionalWork;
        $companyJob->vacationDays = $request->vacationDays;
        $companyJob->rent = $request->rent;
        $companyJob->food = $request->food;
        $companyJob->otherDescription = $request->otherDescription;
        $companyJob->countryOfOrigin = $request->countryOfOrigin;
        $companyJob->position_id = $request->position_id;
        $companyJob->real_position = $request->real_position;
        $companyJob->country_id = $request->country_id;

        $companyForThisJob = Company::where('id', $companyJob->company_id)->first()->nameOfCompany;

        if ($companyJob->save()) {
            $notificationData = [
                'message' => $companyForThisJob . ' updated job posting: ' . $request->job_title,
                'type' => 'job_posting_updated'
            ];

            $notification = NotificationRepository::createNotification($notificationData);
            UsersNotificationRepository::createNotificationForUsers($notification);

            if ($this->isStaff() && $request->has('agentsIds')) {
                foreach ($request->agentsIds as $agentId) {
                    AssignedJob::firstOrCreate([
                        'user_id' => $agentId,
                        'company_job_id' => $companyJob->id,
                    ]);
                }
            }

            return response()->json([
                "status" => "success",
                "message" => "Job updated successfully",
                "data" => $companyJob,
                "changes_pending_approval" => false
            ], 200);
        }

        return response()->json(['message' => 'Job update failed'], 400);
    }

    public function destroy($id)
    {
        if ($this->isStaff()) {
            $companyJob = CompanyJob::find($id);

            if (!$companyJob) {
                return response()->json([
                    "status" => "error",
                    "message" => "Job not found",
                ], 404);
            }

            $companyJob->delete();

            return response()->json([
                "status" => "success",
                "message" => "Job deleted successfully",
            ], 200);
        }

        return response()->json([
            "status" => "error",
            "message" => "Unauthorized access",
        ], 403);
    }

    /**
     * Update job posting status with explicit status value
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->checkPermission(Permission::COMPANY_JOB_REQUESTS_APPROVE)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,active,inactive,filled,rejected,revision_requested'
        ]);

        $companyJob = CompanyJob::find($id);

        if (!$companyJob) {
            return response()->json([
                "status" => "error",
                "message" => "Job not found",
            ], 404);
        }

        $oldStatus = $companyJob->status;
        $newStatus = $request->status;

        $companyJob->status = $newStatus;

        // Set approval timestamp when moving to active for the first time
        if ($newStatus === CompanyJob::STATUS_ACTIVE && $oldStatus === CompanyJob::STATUS_PENDING) {
            $companyJob->approved_at = now();
            $companyJob->approved_by = $user->id;
        }

        // Set published_at on first activation
        if ($newStatus === CompanyJob::STATUS_ACTIVE && !$companyJob->published_at) {
            $companyJob->published_at = now();
        }

        $companyJob->save();

        return response()->json([
            "status" => "success",
            "message" => "Job posting status updated to {$newStatus}",
            "data" => [
                "id" => $companyJob->id,
                "status" => $companyJob->status,
                "approved_at" => $companyJob->approved_at,
                "approved_by" => $companyJob->approved_by,
                "published_at" => $companyJob->published_at
            ]
        ], 200);
    }

    /**
     * Submit a revision request for an active/filled job posting.
     * Used by company users who want to edit their active job postings.
     */
    private function submitRevisionRequest(Request $request, CompanyJob $companyJob, $user)
    {
        // Build proposed data from request
        $proposedData = [];

        // Handle job_title from position_id if provided
        if ($request->position_id) {
            $position = \App\Models\Position::find($request->position_id);
            $proposedData['job_title'] = $position ? $position->jobPosition : $request->job_title;
        } elseif ($request->has('job_title')) {
            $proposedData['job_title'] = $request->job_title;
        }

        // Collect all revision fields from request
        $revisionFields = [
            'job_description', 'number_of_positions', 'contract_type',
            'requirementsForCandidates', 'salary', 'bonus', 'workTime',
            'additionalWork', 'vacationDays', 'rent', 'food', 'otherDescription'
        ];

        foreach ($revisionFields as $field) {
            if ($request->has($field)) {
                $proposedData[$field] = $request->$field;
            }
        }

        // Filter to only include fields that actually changed
        $changedData = [];
        foreach ($proposedData as $field => $newValue) {
            $oldValue = $companyJob->$field;
            if ((string) $oldValue !== (string) $newValue) {
                $changedData[$field] = $newValue;
            }
        }

        if (empty($changedData)) {
            return response()->json([
                "status" => "success",
                "message" => "No changes detected",
                "data" => $companyJob,
                "revision_submitted" => false
            ], 200);
        }

        // Submit the revision
        $companyJob->submitRevision($changedData, $user->id);

        // Create notification for staff about pending revision
        $companyName = Company::where('id', $companyJob->company_id)->first()->nameOfCompany;
        $notificationData = [
            'message' => $companyName . ' requested revision for job posting: ' . $companyJob->job_title,
            'type' => 'job_posting_revision_request'
        ];
        $notification = NotificationRepository::createNotification($notificationData);
        UsersNotificationRepository::createNotificationForUsers($notification);

        return response()->json([
            "status" => "success",
            "message" => "Revision request submitted for approval",
            "data" => $companyJob->fresh(),
            "revision_submitted" => true,
            "changes_count" => count($changedData)
        ], 200);
    }

    /**
     * Approve a pending revision on a job posting.
     * Staff only - applies all proposed changes.
     */
    public function approveRevision(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->checkPermission(Permission::JOB_POSTINGS_REVISIONS_MANAGE)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        $companyJob = CompanyJob::find($id);

        if (!$companyJob) {
            return response()->json([
                "status" => "error",
                "message" => "Job not found",
            ], 404);
        }

        if (!$companyJob->hasPendingRevision()) {
            return response()->json([
                "status" => "error",
                "message" => "No pending revision to approve",
            ], 400);
        }

        // Get revision diff before approving (for notification)
        $diff = $companyJob->getRevisionDiff();

        // Approve the revision
        $companyJob->approveRevision($user->id);

        // Create notification
        $companyName = Company::where('id', $companyJob->company_id)->first()->nameOfCompany;
        $notificationData = [
            'message' => 'Revision approved for ' . $companyName . ' job posting: ' . $companyJob->job_title,
            'type' => 'job_posting_revision_approved'
        ];
        $notification = NotificationRepository::createNotification($notificationData);
        UsersNotificationRepository::createNotificationForUsers($notification);

        return response()->json([
            "status" => "success",
            "message" => "Revision approved and changes applied",
            "data" => $companyJob->fresh(),
            "changes_applied" => array_keys($diff)
        ], 200);
    }

    /**
     * Reject a pending revision on a job posting.
     * Staff only - discards all proposed changes.
     */
    public function rejectRevision(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->checkPermission(Permission::JOB_POSTINGS_REVISIONS_MANAGE)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        $companyJob = CompanyJob::find($id);

        if (!$companyJob) {
            return response()->json([
                "status" => "error",
                "message" => "Job not found",
            ], 404);
        }

        if (!$companyJob->hasPendingRevision()) {
            return response()->json([
                "status" => "error",
                "message" => "No pending revision to reject",
            ], 400);
        }

        // Reject the revision
        $companyJob->rejectRevision();

        // Create notification
        $companyName = Company::where('id', $companyJob->company_id)->first()->nameOfCompany;
        $notificationData = [
            'message' => 'Revision rejected for ' . $companyName . ' job posting: ' . $companyJob->job_title,
            'type' => 'job_posting_revision_rejected'
        ];
        $notification = NotificationRepository::createNotification($notificationData);
        UsersNotificationRepository::createNotificationForUsers($notification);

        return response()->json([
            "status" => "success",
            "message" => "Revision rejected",
            "data" => $companyJob->fresh()
        ], 200);
    }

    /**
     * Get the pending revision details for a job posting.
     */
    public function getRevision($id)
    {
        $user = Auth::user();

        if (!$this->checkPermission(Permission::JOB_POSTINGS_READ)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        $companyJob = CompanyJob::with(['revisionRequestedBy'])->find($id);

        if (!$companyJob) {
            return response()->json([
                "status" => "error",
                "message" => "Job not found",
            ], 404);
        }

        if (!$companyJob->hasPendingRevision()) {
            return response()->json([
                "status" => "success",
                "message" => "No pending revision",
                "data" => null
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "message" => "Revision retrieved successfully",
            "data" => [
                "job_id" => $companyJob->id,
                "job_title" => $companyJob->job_title,
                "status" => $companyJob->status,
                "revision_diff" => $companyJob->getRevisionDiff(),
                "requested_by" => $companyJob->revisionRequestedBy ? [
                    "id" => $companyJob->revisionRequestedBy->id,
                    "full_name" => trim($companyJob->revisionRequestedBy->firstName . ' ' . $companyJob->revisionRequestedBy->lastName),
                    "email" => $companyJob->revisionRequestedBy->email,
                ] : null,
                "requested_at" => $companyJob->revision_requested_at,
            ]
        ], 200);
    }
}
