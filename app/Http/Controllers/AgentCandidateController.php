<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\Education;
use App\Models\Experience;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $person = new Candidate();

        $person->status_id = $request->status_id;
        $person->type_id = "3";
        $person->company_id = $request->company_id;
        $person->gender = $request->gender;
        $person->email = $request->email;
        $person->nationality = $request->nationality;
        $person->date = $request->date;
        $person->phoneNumber = $request->phoneNumber;
        $person->address = $request->address;
        $person->passport = $request->passport;
        $person->fullName = $request->fullName;
        $person->fullNameCyrillic = $request->fullNameCyrillic;
        $person->birthday = $request->birthday;
        $person->placeOfBirth = $request->placeOfBirth;
        $person->country = $request->country;
        $person->area = $request->area;
        $person->areaOfResidence = $request->areaOfResidence;
        $person->addressOfResidence = $request->addressOfResidence;
        $person->periodOfResidence = $request->periodOfResidence;
        $person->passportValidUntil = $request->passportValidUntil;
        $person->passportIssuedBy = $request->passportIssuedBy;
        $person->passportIssuedOn = $request->passportIssuedOn;
        $person->addressOfWork = $request->addressOfWork;
        $person->nameOfFacility = $request->nameOfFacility;
        $person->education = $request->education;
        $person->specialty = $request->specialty;
        $person->qualification = $request->qualification;
        $person->contractExtensionPeriod = $request->contractExtensionPeriod;
        $person->salary = $request->salary;
        $person->workingTime = $request->workingTime;
        $person->workingDays = $request->workingDays;
        $person->martialStatus = $request->martialStatus;
        $person->contractPeriod = $request->contractPeriod;
        $person->contractType = $request->contractType;
        $person->position_id = $request->position_id;
        $person->dossierNumber = $request->dossierNumber;
        $person->notes = $request->notes;
        $person->user_id = $request->user_id;
        $person->addedBy = Auth::user()->id;
        $educations = $request->educations ?? [];
        $experiences = $request->experiences ?? [];
        
        if($person->save()){

            if(count($educations) > 0){
                foreach ($educations as $education) {
                    $newEducation = new Education();
                    $newEducation->candidate_id = $person->id;
                    $newEducation->school_name = $education['school_name'];
                    $newEducation->degree = $education['degree'];
                    $newEducation->field_of_study = $education['field_of_study'];
                    $newEducation->start_date = $education['start_date'];
                    $newEducation->end_date = $education['end_date'];
                    $newEducation->save();
                }
            }

            if(count($experiences) > 0){
                foreach ($experiences as $experience) {
                    $newExperience = new Experience();
                    $newExperience->candidate_id = $person->id;
                    $newExperience->company_name = $experience['company_name'];
                    $newExperience->position = $experience['position'];
                    $newExperience->start_date = $experience['start_date'];
                    $newExperience->end_date = $experience['end_date'];
                    $newExperience->save();
                }
            }

            $notificationData = [
                'message' => 'Agent' . ' ' . Auth::user()->name . ' ' .  'added candidate to job',
                'type' => 'Agent add Candidate for Assigned Job',
            ];
    
            $candidateData = [
                'user_id' => Auth::user()->id,
                'company_job_id' => $request->company_job_id,
                'candidate_id' => $person->id,
            ];
    
            $agentCandidate = new AgentCandidate();
    
            $agentCandidate->user_id = $candidateData['user_id'];
            $agentCandidate->candidate_id = $candidateData['candidate_id'];
            $agentCandidate->company_job_id = $candidateData['company_job_id'];
    
            $agentCandidate->save();
    
    
            $notification = NotificationRepository::createNotification($notificationData);
            UsersNotificationRepository::createNotificationForUsers($notification);
    
            return response()->json(
                [
                    'message' => 'Candidate added successfully',
                    'agentCandidate' => $agentCandidate,
                ],
                200
            );
        } else {
            return response()->json(['message' => 'Failed to add candidate'], 500);
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
    public function getAllCandidatesFromAgents(Request $request)
    {
        try {
            $user_id = Auth::user()->id;

            if ($request->company_job_id !== null) {
                if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                    $candidates = DB::table('agent_candidates')
                        ->join('candidates', 'agent_candidates.candidate_id', '=', 'candidates.id')
                        ->join('users', 'agent_candidates.user_id', '=', 'users.id')
                        ->select('candidates.*', 'users.email')
                        ->where('agent_candidates.company_job_id', $request->company_job_id)
                        ->paginate(20);
                } else if (Auth::user()->role_id == 4) {
                    $candidates = DB::table('agent_candidates')
                        ->join('candidates', 'agent_candidates.candidate_id', '=', 'candidates.id')
                        ->join('users', 'agent_candidates.user_id', '=', 'users.id')
                        ->select('candidates.*', 'users.email')
                        ->where('agent_candidates.user_id', $user_id)
                        ->where('agent_candidates.company_job_id', $request->company_job_id)
                        ->paginate(20);
                }
            } else {
                $candidates = DB::table('agent_candidates')
                    ->join('candidates', 'agent_candidates.candidate_id', '=', 'candidates.id')
                    ->join('users', 'agent_candidates.user_id', '=', 'users.id')
                    ->select('candidates.*', 'users.email')
                    ->where('agent_candidates.user_id', $user_id)
                    ->paginate(20);
            }





            return response()->json(['candidates' => $candidates], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get candidates'], 500);
        }
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
