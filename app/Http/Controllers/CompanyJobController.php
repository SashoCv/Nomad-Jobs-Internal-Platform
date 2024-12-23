<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\AssignedJob;
use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\User;
use App\Models\UserOwner;
use App\Notifications\CompanyJobCreatedNotification;
use App\Repository\NotificationRepository;
use App\Repository\SendEmailRepositoryForCreateCompanyJob;
use App\Repository\UsersNotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as FacadesNotification;

class CompanyJobController extends Controller
{

    public function __construct(
        private UsersNotificationRepository $usersNotificationRepository,
        private NotificationRepository $notificationRepository,
        private SendEmailRepositoryForCreateCompanyJob $sendEmailRepositoryForCreateCompanyJob
    ) {
    }


    public function index(Request $request)
    {
        $user = Auth::user();
        $roleId = $user->role_id;
        $contractType = $request->contract_type;

        $query = DB::table('company_jobs')
            ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
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
                'company_jobs.deleted_at'
            )
            ->whereNull('company_jobs.deleted_at')
            ->orderBy('company_jobs.created_at', 'desc');

        if ($contractType) {
            $query->where('company_jobs.contract_type', $contractType);
        }

        switch ($roleId) {
            case 1:
            case 2:
                if ($companyId = $request->company_id) {
                    $query->where('companies.id', $companyId);
                }
                break;

            case 3:
                // Company-specific role
                $query->where('company_jobs.company_id', $user->company_id);
                break;

            case 5:
                // COMPANY OWNER
                $companyIds = UserOwner::where('user_id', $user->id)
                    ->pluck('company_id')
                    ->toArray();
                $query->whereIn('company_jobs.company_id', $companyIds);
                break;

            case 4:
                // AGENT
                $companyJobIds = AssignedJob::where('user_id', $user->id)
                    ->pluck('company_job_id')
                    ->toArray();
                $query->whereIn('company_jobs.id', $companyJobIds);
                break;

            default:
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized access",
                ], 403);
        }

        $allJobPostings = $query->paginate();

        // Return the response
        return response()->json([
            "status" => "success",
            "message" => "Job retrieved successfully",
            "data" => $allJobPostings
        ], 200);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $companyJob = new CompanyJob();

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->contract_type = $request->contract_type;
            $companyJob->requirementsForCandidates= $request->requirementsForCandidates;
            $companyJob->salary= $request->salary;
            $companyJob->bonus= $request->bonus;
            $companyJob->workTime= $request->workTime;
            $companyJob->additionalWork= $request->additionalWork;
            $companyJob->vacationDays= $request->vacationDays;
            $companyJob->rent= $request->rent;
            $companyJob->food= $request->food;
            $companyJob->otherDescription= $request->otherDescription;


            if ($companyJob->save()) {

                $companyName = Company::where('id', $request->company_id)->first();
                $companyForThisJob = $companyName->nameOfCompany;



                $notificationMessages = array(
                    'message' =>  $companyForThisJob . ' created new job posting: ' . $request->job_title,
                    'type' => 'job_posting'
                );

                $notification_id = NotificationRepository::createNotification($notificationMessages);
                UsersNotificationRepository::createNotificationForUsers($notification_id);
                $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);

                if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                    if ($request->agentsIds) {
                        $agents = $request->agentsIds;
                        foreach ($agents as $agentId) {
                            $assignedJob = new AssignedJob();
                            $assignedJob->user_id = $agentId;
                            $assignedJob->company_job_id = $companyJob->id;

                            $assignedJob->save();
                        }
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
        } else if (Auth::user()->role_id == 3) {
            $companyJob = new CompanyJob();

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = Auth::user()->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->contract_type = $request->contract_type;
            $companyJob->requirementsForCandidates= $request->requirementsForCandidates;
            $companyJob->salary= $request->salary;
            $companyJob->bonus= $request->bonus;
            $companyJob->workTime= $request->workTime;
            $companyJob->additionalWork= $request->additionalWork;
            $companyJob->vacationDays= $request->vacationDays;
            $companyJob->rent= $request->rent;
            $companyJob->food= $request->food;
            $companyJob->otherDescription= $request->otherDescription;


            if ($companyJob->save()) {

                $companyName = Company::where('id', Auth::user()->company_id)->first();
                $companyForThisJob = $companyName->nameOfCompany;

                $notificationData = [
                    'message' => $companyForThisJob . ' created new job posting: ' . $request->job_title,
                    'type' => "job_posting"
                ];



                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);
                $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);


                return response()->json([
                    "status" => "success",
                    "message" => "Job created successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'Job creation failed'], 400);
            }
        } else if (Auth::user()->role_id == 5) {
            $companyJob = new CompanyJob();

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->requirementsForCandidates= $request->requirementsForCandidates;
            $companyJob->salary= $request->salary;
            $companyJob->bonus= $request->bonus;
            $companyJob->workTime= $request->workTime;
            $companyJob->additionalWork= $request->additionalWork;
            $companyJob->vacationDays= $request->vacationDays;
            $companyJob->rent= $request->rent;
            $companyJob->food= $request->food;
            $companyJob->otherDescription= $request->otherDescription;

            if ($companyJob->save()) {

                $companyName = Company::where('id', $request->company_id)->first();
                $companyForThisJob = $companyName->nameOfCompany;

                $notificationData = [
                    'message' => $companyForThisJob . ' created new job posting: ' . $request->job_title,
                    'type' => "job_posting"
                ];



                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);
                $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);


                return response()->json([
                    "status" => "success",
                    "message" => "Job created successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'Job creation failed'], 400);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 5) {
            $companyJob = CompanyJob::where('id', $id)->first();
            $company = Company::where('id', $companyJob->company_id)->first();
            $companyJob->companyImage = $company->logoPath;
            $companyJob->companyCity = $company->companyCity;
            $companyJob->companyName = $company->nameOfCompany;

            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $assignedJobs = AssignedJob::where('company_job_id', $id)->get();
                $agentsIds = [];
                foreach ($assignedJobs as $assignedJob) {
                    $agent = User::where('id', $assignedJob->user_id)->first();
                    $agentsIds[] = $agent->id;
                }
                $companyJob->agentsIds = $agentsIds;
            }

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } else if (Auth::user()->role_id == 3) {
            $companyJob = CompanyJob::where('id', $id)->where('company_id', Auth::user()->company_id)->first();
            $company = Company::where('id', $companyJob->company_id)->first();
            $companyJob->companyImage = $company->logoPath;
            $companyJob->companyCity = $company->companyCity;
            $companyJob->companyName = $company->nameOfCompany;


            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } else if (Auth::user()->role_id == 4) {
            $assignedJob = AssignedJob::where('user_id', Auth::user()->id)->where('company_job_id', $id)->first();
            if ($assignedJob) {

                $companyJob = CompanyJob::where('id', $id)->first();
                $company = Company::where('id', $companyJob->company_id)->first();
                $companyJob->companyImage = $company->logoPath;
                $companyJob->companyCity = $company->companyCity;
                $companyJob->companyName = $company->nameOfCompany;


                return response()->json([
                    "status" => "success",
                    "message" => "Job retrieved successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'You are not authorized to view this job'], 401);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyJob $companyJob)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyJob $companyJob)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 5) {
            $companyJob = CompanyJob::find($request->id);

            $companyJob->user_id = $request->user_id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->contract_type = $request->contract_type;
            $companyJob->requirementsForCandidates= $request->requirementsForCandidates;
            $companyJob->salary= $request->salary;
            $companyJob->bonus= $request->bonus;
            $companyJob->workTime= $request->workTime;
            $companyJob->additionalWork= $request->additionalWork;
            $companyJob->vacationDays= $request->vacationDays;
            $companyJob->rent= $request->rent;
            $companyJob->food= $request->food;
            $companyJob->otherDescription= $request->otherDescription;

            $companyForThisJob = Company::where('id', $request->company_id)->first();
            $companyForThisJob = $companyForThisJob->nameOfCompany;

            if ($companyJob->save()) {

                $notificationData = [
                    'message' =>  $companyForThisJob . ' updated new job posting: ' . $request->job_title,
                    'type' => 'job_posting_updated'
                ];

                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                    if ($request->agentsIds || $request->agentsIds == []) {

                        $deleteAgentsForAssignedJob = AssignedJob::where('company_job_id', $companyJob->id)->get();
                        foreach ($deleteAgentsForAssignedJob as $deleteAgentForAssignedJob) {
                            $deleteAgentForAssignedJob->delete();
                        }

                        $agents = $request->agentsIds;
                        if ($agents != []) {
                            foreach ($agents as $agentId) {
                                $assignedJob = new AssignedJob();
                                $assignedJob->user_id = $agentId;
                                $assignedJob->company_job_id = $companyJob->id;

                                $assignedJob->save();
                            }
                        }
                    }
                }

                return response()->json([
                    "status" => "success",
                    "message" => "Job updated successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'Job update failed'], 400);
            }
        } else
            if (Auth::user()->role_id == 3) {
            $companyJob = CompanyJob::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();

            $companyJob->user_id = $request->user_id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;
            $companyJob->contract_type = $request->contract_type;
            $companyJob->requirementsForCandidates= $request->requirementsForCandidates;
            $companyJob->salary= $request->salary;
            $companyJob->bonus= $request->bonus;
            $companyJob->workTime= $request->workTime;
            $companyJob->additionalWork= $request->additionalWork;
            $companyJob->vacationDays= $request->vacationDays;
            $companyJob->rent= $request->rent;
            $companyJob->food= $request->food;
            $companyJob->otherDescription= $request->otherDescription;

            $companyForThisJob = Company::where('id', Auth::user()->company_id)->first();
            $companyForThisJob = $companyForThisJob->nameOfCompany;
            if ($companyJob->save()) {

                $notificationData = [
                    'message' => $companyForThisJob . ' updated new job posting: ' . $request->job_title,
                    'type' => 'job_posting_updated'
                ];

                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                return response()->json([
                    "status" => "success",
                    "message" => "Job retrieved successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'Job update failed'], 400);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companyJob = CompanyJob::find($id);

            $allCandidatesFromAgent = AgentCandidate::where('company_job_id', $id)->get();
            foreach ($allCandidatesFromAgent as $candidate) {
                $candidate->delete();
            }
            if ($companyJob->delete()) {
                return response()->json([
                    "status" => "success",
                    "message" => "Job deleted successfully",
                ], 200);
            } else {
                return response()->json(['message' => 'Job deletion failed'], 400);
            }
        }
    }
}
