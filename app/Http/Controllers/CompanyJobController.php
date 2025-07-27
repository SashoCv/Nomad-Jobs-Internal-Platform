<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\User;
use App\Models\UserOwner;
use App\Models\Role;
use App\Models\AssignedJob;
use App\Notifications\CompanyJobCreatedNotification;
use App\Repository\NotificationRepository;
use App\Repository\SendEmailRepositoryForCreateCompanyJob;
use App\Repository\UsersNotificationRepository;
use App\Traits\HasRolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $query = DB::table('company_jobs')
            ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
            ->leftJoin('agent_candidates', function($join) {
                $join->on('agent_candidates.company_job_id', '=', 'company_jobs.id')
                     ->where('agent_candidates.status_for_candidate_from_agent_id', '=', 3);
            })
            ->select(
                'company_jobs.id',
                'companies.logoPath',
                'companies.companyCity',
                'company_jobs.company_id',
                'company_jobs.job_title',
                'company_jobs.number_of_positions',
                'company_jobs.contract_type',
                'company_jobs.job_description',
                'companies.nameOfCompany',
                'company_jobs.created_at',
                'company_jobs.updated_at',
                'company_jobs.deleted_at',
                DB::raw("COUNT(agent_candidates.id) as candidates_count")
            )
            ->whereNull('company_jobs.deleted_at')
            ->groupBy(
                'company_jobs.id',
                'companies.logoPath',
                'companies.companyCity',
                'company_jobs.company_id',
                'company_jobs.job_title',
                'company_jobs.number_of_positions',
                'company_jobs.contract_type',
                'company_jobs.job_description',
                'companies.nameOfCompany',
                'company_jobs.created_at',
                'company_jobs.updated_at',
                'company_jobs.deleted_at'
            )
            ->orderBy('company_jobs.created_at', 'desc');

        if ($contractType) {
            $query->where('company_jobs.contract_type', $contractType);
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

        if ($this->isStaff() || $user->role_id == Role::COMPANY_USER || $user->role_id == Role::COMPANY_OWNER) {
            $companyJob = new CompanyJob();

            $companyJob->user_id = $user->id;
            $companyJob->company_id = $user->role_id == Role::COMPANY_USER ? $user->company_id : $request->company_id;
            $companyJob->job_title = $request->job_title;
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

            if ($companyJob->save()) {
                $companyName = Company::where('id', $companyJob->company_id)->first();
                $companyForThisJob = $companyName->nameOfCompany;

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

        if ($this->isStaff() || $user->role_id == Role::COMPANY_OWNER || $user->role_id == Role::COMPANY_USER) {
            $companyJob = CompanyJob::where('id', $id)->first();

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

            if ($this->isStaff()) {
                $assignedJobs = AssignedJob::where('company_job_id', $id)->get();
                $companyJob->agentsIds = $assignedJobs->pluck('user_id')->toArray();
            }

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

        if ($this->isStaff() || $user->role_id == Role::COMPANY_OWNER || $user->role_id == Role::COMPANY_USER) {
            $companyJob = CompanyJob::find($companyJobId);
            $companyJob->job_title = $request->job_title;
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
                    "data" => $companyJob
                ], 200);
            }

            return response()->json(['message' => 'Job update failed'], 400);
        }

        return response()->json([
            "status" => "error",
            "message" => "Unauthorized access",
        ], 403);
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
}
