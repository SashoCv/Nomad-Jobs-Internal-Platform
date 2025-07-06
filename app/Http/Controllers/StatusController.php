<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailForArrivalCandidates;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Category;
use App\Models\Status;
use App\Models\Statushistory;
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

            $candidate_id = $request->candidate_id;
            $status_id = $request->status_id;
            $description = $request->description ?? null;
            $statusDate = $request->statusDate ?? Carbon::now()->format('Y-m-d');

            $statusHistory = new Statushistory();
            $statusHistory->candidate_id = $candidate_id;
            $statusHistory->status_id = $status_id;
            $statusHistory->statusDate = Carbon::createFromFormat('m-d-Y', $request->statusDate)->format('Y-m-d');
            $statusHistory->description = $description;

            if (!$statusHistory->save()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to save status history',
                    'data' => [],
                ]);
            }
        }


//            $companyForThisCandidate = $candidate->company_id;
//            $companyName = Company::where('id', $companyForThisCandidate)->first();

//            $notificationData = [
//                'message' => 'Status for candidate ' . $candidate->fullNameCyrillic . ' has been changed', 'company' => $companyName->nameOfCompany,
//                'type' => 'Changed Status',
//            ];
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
