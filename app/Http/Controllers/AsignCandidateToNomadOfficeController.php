<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Traits\HasRolePermissions;
use App\Models\AsignCandidateToNomadOffice;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsignCandidateToNomadOfficeController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            if($this->isStaff()){
                $candidatesAddByAgent = Candidate::with(['company', 'status', 'position', 'user','cases','agentCandidates'])
               ->whereHas('agentCandidates')->get();
            } else {
                $candidatesAddByAgent = [];
            }


            return response()->json([
                'message' => 'Candidates added by agent',
                'data' => $candidatesAddByAgent
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get candidates added by agent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignCandidateToNomadOffice(Request $request)
    {
        try {
            $companyId = $request->company_id;
            $nomadOfficeId = $request->nomad_office_id;
            $allCandidatesFromAgentForThisCompany = AgentCandidate::with(['candidate', 'companyJob', 'statusForCandidateFromAgent', 'user'])
                ->whereHas('companyJob', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('status_for_candidate_from_agent_id', 3)
                ->where('nomad_office_id', null)
                ->get();

            $candidates = [];

            foreach ($allCandidatesFromAgentForThisCompany as $candidateFromAgent){
                $candidates[]= $candidateFromAgent['candidate'];
            }

            $candidatesIds = array_column($candidates, 'id');

            foreach ($candidatesIds as $candidatesId){
                $assignCandidateToNomadOffice = AgentCandidate::where('candidate_id', $candidatesId)->first();
                $assignCandidateToNomadOffice->nomad_office_id = $nomadOfficeId;
                $assignCandidateToNomadOffice->save();
            }

            if($candidatesIds == []){
                return response()->json([
                    'message' => 'No candidates found for this company',
                    'data' => $candidatesIds
                ], 404);
            }

            return response()->json([
                'message' => 'Candidates assigned to nomad office successfully',
                'data' => $candidatesIds
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign candidate to nomad office',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AsignCandidateToNomadOffice  $asignCandidateToNomadOffice
     * @return \Illuminate\Http\Response
     */
    public function show(AsignCandidateToNomadOffice $asignCandidateToNomadOffice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AsignCandidateToNomadOffice  $asignCandidateToNomadOffice
     * @return \Illuminate\Http\Response
     */
    public function edit(AsignCandidateToNomadOffice $asignCandidateToNomadOffice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AsignCandidateToNomadOffice  $asignCandidateToNomadOffice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AsignCandidateToNomadOffice $asignCandidateToNomadOffice)
    {
        //
    }

    /**
     * Assign HR employee to approved candidate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id (candidate_from_agent_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateHRData(Request $request, $id)
    {
        try {
            $agentCandidate = AgentCandidate::with('candidate')->findOrFail($id);
            $candidateId = $agentCandidate->candidate_id;
            $nomadOfficeId = $request->input('hr_employee_id');
            $adminId = Auth::id();

            // Check if assignment already exists
            $existingAssignment = AsignCandidateToNomadOffice::where('candidate_id', $candidateId)->first();

            if ($existingAssignment) {
                // Update existing assignment
                $existingAssignment->nomad_office_id = $nomadOfficeId;
                $existingAssignment->admin_id = $adminId;
                $existingAssignment->save();
                $assignment = $existingAssignment;
            } else {
                // Create new assignment
                $assignment = AsignCandidateToNomadOffice::create([
                    'admin_id' => $adminId,
                    'nomad_office_id' => $nomadOfficeId,
                    'candidate_id' => $candidateId,
                ]);
            }

            return response()->json([
                'message' => 'HR employee assigned successfully',
                'data' => $assignment
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign HR employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AsignCandidateToNomadOffice  $asignCandidateToNomadOffice
     * @return \Illuminate\Http\Response
     */
    public function destroy(AsignCandidateToNomadOffice $asignCandidateToNomadOffice)
    {
        //
    }
}
