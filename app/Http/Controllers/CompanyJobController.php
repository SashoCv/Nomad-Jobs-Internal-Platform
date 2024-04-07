<?php

namespace App\Http\Controllers;

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
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $companyId = $request->company_id;

            $allJobPostingsQuery = DB::table('company_jobs')
                ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
                ->select('company_jobs.id', 'company_jobs.company_id', 'company_jobs.job_title', 'company_jobs.number_of_positions', 'company_jobs.job_description', 'companies.nameOfCompany', 'company_jobs.created_at', 'company_jobs.updated_at', 'company_jobs.deleted_at')
                ->whereNull('company_jobs.deleted_at');

            if ($companyId) {
                $allJobPostingsQuery->where('companies.id', $companyId);
            }

            $allJobPostings = $allJobPostingsQuery->get();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        } else if (Auth::user()->role_id == 3) {
            $allJobPostings = DB::table('company_jobs')
                ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
                ->where('company_jobs.company_id', Auth::user()->company_id)
                ->select('company_jobs.id', 'company_jobs.company_id', 'company_jobs.job_title', 'company_jobs.number_of_positions', 'company_jobs.job_description', 'companies.nameOfCompany', 'company_jobs.created_at', 'company_jobs.updated_at', 'company_jobs.deleted_at')
                ->where('company_jobs.deleted_at', null)
                ->get();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        } else if (Auth::user()->role_id == 5) {
            $userOwner = UserOwner::where('user_id', Auth::user()->id)->get();
            $companyIds = [];
            foreach ($userOwner as $owner) {
                $companyIds[] = $owner->company_id;
            }

            $allJobPostings = DB::table('company_jobs')
                ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
                ->where('company_jobs.company_id', $companyIds)
                ->select('company_jobs.id', 'company_jobs.company_id', 'company_jobs.job_title', 'company_jobs.number_of_positions', 'company_jobs.job_description', 'companies.nameOfCompany', 'company_jobs.created_at', 'company_jobs.updated_at', 'company_jobs.deleted_at')
                ->where('company_jobs.deleted_at', null)
                ->get();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        } else if (Auth::user()->role_id == 4) {

            $assignedJobs = AssignedJob::where('user_id', Auth::user()->id)->get();

            $companyJobIds = [];
            foreach ($assignedJobs as $assignedJob) {
                $companyJobIds[] = $assignedJob->company_job_id;
            }

            $allJobPostings = DB::table('company_jobs')
                ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
                ->whereIn('company_jobs.id', $companyJobIds)
                ->select('company_jobs.id', 'company_jobs.company_id', 'company_jobs.job_title', 'company_jobs.number_of_positions', 'company_jobs.job_description', 'companies.nameOfCompany', 'company_jobs.created_at', 'company_jobs.updated_at', 'company_jobs.deleted_at')
                ->where('company_jobs.deleted_at', null)
                ->get();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        }
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
        } else if (Auth::role()->id === 5) {
            $companyJob = new CompanyJob();

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;


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
            $companyJob = CompanyJob::with(['company', 'user'])->where('id', $id)->first();

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
            $companyJob = CompanyJob::with(['company', 'user'])->where('id', $id)->where('company_id', Auth::user()->company_id)->first();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } else if (Auth::user()->role_id == 4) {
            $assignedJob = AssignedJob::where('user_id', Auth::user()->id)->where('company_job_id', $id)->first();
            if ($assignedJob) {
                $companyJob = CompanyJob::with(['company', 'user'])->where('id', $id)->first();
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

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = Auth::user()->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;
            $companyJob->job_description = $request->job_description;


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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companyJob = CompanyJob::find($id);
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
