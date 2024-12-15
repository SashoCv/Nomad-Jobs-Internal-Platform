<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailForArrivalCandidates;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Category;
use App\Models\Status;
use App\Models\UserNotification;
use App\Repository\NotificationRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Candidate;
use App\Models\Company;
use App\Repository\SendEmailRepositoryForCreateCompanyJob;
use App\Repository\UsersNotificationRepository;

class StatusController extends Controller
{

    public function __construct(
        private UsersNotificationRepository $usersNotificationRepository,
        private NotificationRepository $notificationRepository,
        private SendEmailRepositoryForCreateCompanyJob $sendEmailRepositoryForCreateCompanyJob
    ) {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
          $statuses = Status::orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $statuses,
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function show(Status $status)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function edit(Status $status)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Status  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatusForCandidate(Request $request)
    {

        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $idForCandidate = $request->candidate_id;
            $changedStatus = $request->status_id;

            $candidate = Candidate::where('id', $idForCandidate)->first();
            $candidate->status_id = $changedStatus;

            $companyForThisCandidate = $candidate->company_id;
            $companyName = Company::where('id', $companyForThisCandidate)->first();

            $notificationData = [
                'message' => 'Status for candidate ' . $candidate->fullNameCyrillic . ' has been changed', 'company' => $companyName->nameOfCompany,
                'type' => 'Changed Status',
            ];


            // implement the sendEmailRepositoryForCreateStatusForCandidate

            if ($candidate->save()) {

                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                if($candidate->status_id == 4){
                    $arrival = new Arrival();

                    $arrival->company_id = $candidate->company_id;
                    $arrival->candidate_id = $candidate->id;
                    $arrival->arrival_date =  null;
                    $arrival->arrival_time = null;
                    $arrival->arrival_location = null;
                    $arrival->arrival_flight = null;
                    $arrival->where_to_stay = null;
                    $arrival->phone_number = null;

                    if ($arrival->save()) {
                        $arrivalCandidate = new ArrivalCandidate();

                        $arrivalCandidate->arrival_id = $arrival->id;
                        $arrivalCandidate->status_arrival_id = 7;  // Poluchil viza
                        $arrivalCandidate->status_description = 'Получил виза';
                        $arrivalCandidate->status_date = Carbon::now()->format('d-m-Y');

                        $arrivalCandidate->save();


                        $category = new Category();
                        $category->nameOfCategory = 'Documents For Arrival Candidates';
                        $category->candidate_id = $request->candidate_id;
                        $category->role_id = 2;
                        $category->isGenerated = 0;
                        $category->save();
                    }

                }

                return response()->json([
                    'status' => 200,
                    'message' => 'you have updated the status',
                    'data' => $candidate,
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => 'something went wrong',
                    'data' => [],
                ]);
            }
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'you dont have permissions',
                'data' => [],
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function destroy(Status $status)
    {
        //
    }
}
