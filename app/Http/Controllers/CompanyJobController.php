<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyJob;
use App\Models\User;
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


    public function index()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $allJobPostings = DB::table('company_jobs')
                ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
                ->select('company_jobs.id', 'company_jobs.company_id', 'company_jobs.job_title', 'company_jobs.number_of_positions', 'company_jobs.job_description', 'companies.nameOfCompany', 'company_jobs.created_at', 'company_jobs.updated_at', 'company_jobs.deleted_at')
                ->where('company_jobs.deleted_at', null)
                ->get();


            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        } else {
            if (Auth::user()->role_id == 3) {
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
            }
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

                $companyName = Company::where('id', Auth::user()->company_id)->first(['nameOfCompany']);

                $notificationMessages = array(
                    'message' => 'Job created successfully',
                    'type' => $request->job_title . " created successfully and for " . $request->number_of_positions . " positions for company " . $companyName . " by " . Auth::user()->email,
                );

                $notification_id = NotificationRepository::createNotification($notificationMessages);
                UsersNotificationRepository::createNotificationForUsers($notification_id);
                $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);


                return response()->json([
                    "status" => "success",
                    "message" => "Job created successfully",
                    "data" => $companyJob,
                ], 200);
            } else {
                return response()->json(['message' => 'Job creation failed'], 400);
            }
        } else {
            if (Auth::user()->role_id == 3) {
                $companyJob = new CompanyJob();

                $companyJob->user_id = Auth::user()->id;
                $companyJob->company_id = Auth::user()->company_id;
                $companyJob->job_title = $request->job_title;
                $companyJob->number_of_positions = $request->number_of_positions;
                $companyJob->job_description = $request->job_description;


                if ($companyJob->save()) {

                    $companyName = Company::where('id', Auth::user()->company_id)->first(['company_name']);

                    $notificationData = [
                        'message' => 'Job created successfully',
                        'type' => $request->job_title . " created successfully and for " . $request->number_of_positions . " positions for company " . $companyName . " by " . Auth::user()->email,
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
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companyJob = CompanyJob::with(['company', 'user'])->where('id', $id)->first();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } else {
            if (Auth::user()->role_id == 3) {
                $companyJob = CompanyJob::with(['company', 'user'])->where('id', $id)->where('company_id', Auth::user()->company_id)->first();

                return response()->json([
                    "status" => "success",
                    "message" => "Job retrieved successfully",
                    "data" => $companyJob
                ], 200);
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
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companyJob = CompanyJob::find($request->id);

            $companyJob->user_id = Auth::user()->id;
            $companyJob->company_id = $request->company_id;
            $companyJob->job_title = $request->job_title;
            $companyJob->number_of_positions = $request->number_of_positions;

            if ($companyJob->save()) {

                $notificationData = [
                    'message' => 'Job updated successfully',
                    'type' => $request->job_title . " updated successfully and for " . $request->number_of_positions . " positions for company " . $request->company_id . " by " . Auth::user()->email,
                ];

                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                return response()->json([
                    "status" => "success",
                    "message" => "Job updated successfully",
                    "data" => $companyJob
                ], 200);
            } else {
                return response()->json(['message' => 'Job update failed'], 400);
            }
        } else {
            if (Auth::user()->role_id == 3) {
                $companyJob = CompanyJob::where('id', $request->id)->where('company_id', Auth::user()->company_id)->first();

                $companyJob->user_id = Auth::user()->id;
                $companyJob->company_id = Auth::user()->company_id;
                $companyJob->job_title = $request->job_title;
                $companyJob->number_of_positions = $request->number_of_positions;

                if ($companyJob->save()) {

                    $notificationData = [
                        'message' => 'Job updated successfully',
                        'type' => $request->job_title . " updated successfully and for " . $request->number_of_positions . " positions for company " . $request->company_id . " by " . Auth::user()->email,
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
