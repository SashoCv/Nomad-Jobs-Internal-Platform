<?php

namespace App\Http\Controllers;

use App\Models\CompanyJob;
use App\Models\User;
use App\Repository\NotificationRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UsersNotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

            if ($companyJob->save()) {

                $notificationMessages = array(
                    'message' => 'Job created successfully',
                    'type' => $request->job_title . " created successfully and for " . $request->number_of_positions . " positions for company " . $request->company_id . " by " . Auth::user()->email,
                );

                $notification_id = NotificationRepository::createNotification($notificationMessages);
                $resUser = UsersNotificationRepository::createNotificationForUsers($notification_id);


                return response()->json([
                    "status" => "success",
                    "message" => "Job created successfully",
                    "data" => $companyJob,
                    "notification" => $notification_id,
                    "resUser" => $resUser
                ], 200);
            } else {
                return response()->json(['message' => 'Job creation failed'], 400);
            }
        } else {
            if (Auth::user()->role_id == 3) {
                $companyJob = new CompanyJob();

                $companyJob->user_id = Auth::user()->id;
                $companyJob->company_id = Auth::user()->company_id;
                $companyJob->title = $request->job_title;
                $companyJob->number_of_positions = $request->number_of_positions;

                if ($companyJob->save()) {

                    $notificationData = [
                        'message' => 'Job created successfully',
                        'type' => $request->job_title . " created successfully and for " . $request->number_of_positions . " positions for company " . $request->company_id . " by " . Auth::user()->email,
                    ];



                    $notification = NotificationRepository::createNotification($notificationData);
                    UsersNotificationRepository::createNotificationForUsers($notification);

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
            $companyJob = CompanyJob::where('company_id', $id)->get();

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } else {
            if (Auth::user()->role_id == 3) {
                $companyJob = CompanyJob::where('company_id', Auth::user()->company_id)->get();

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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyJob $companyJob)
    {
        //
    }
}
