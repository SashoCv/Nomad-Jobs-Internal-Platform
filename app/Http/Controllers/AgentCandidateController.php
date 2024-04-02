<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentCandidateController extends Controller
{
    public function __construct(
        private UsersNotificationRepository $usersNotificationRepository,
        private NotificationRepository $notificationRepository,
    ) {
    }


    public function agentAddCandidateForAssignedJob(Request $request)
    {
        try {
            $agentCandidate = new AgentCandidate();
            $agentCandidate->user_id = Auth::user()->id;
            $agentCandidate->company_job_id = $request->company_job_id;
            $agentCandidate->candidate_id = $request->candidate_id;

            $notificationData = [
                'message' => 'Agent' . ' ' . Auth::user()->name . ' ' .  'added candidate to job',
                'type' => 'Agent add Candidate for Assigned Job',
            ];

            if($agentCandidate->save()){
                
                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $agentCandidate
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => $e->getMessage()
            ]);
        }
    }

    /**
        * Here i get all candidates for assigned job
     */
    public function getCandidatesForAssignedJob($id)
    {
        try {
            $candidates = Candidate::whereHas('agentCandidates', function ($query) use ($id) {
                $query->where('company_job_id', $id);
            })->get();

            return response()->json(['candidates' => $candidates], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get candidates'], 500);
        }
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
     * @param  \App\Models\AgentCandidate  $agentCandidate
     * @return \Illuminate\Http\Response
     */
    public function show(AgentCandidate $agentCandidate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AgentCandidate  $agentCandidate
     * @return \Illuminate\Http\Response
     */
    public function edit(AgentCandidate $agentCandidate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AgentCandidate  $agentCandidate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgentCandidate $agentCandidate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AgentCandidate  $agentCandidate
     * @return \Illuminate\Http\Response
     */
    public function destroy(AgentCandidate $agentCandidate)
    {
        //
    }
}
