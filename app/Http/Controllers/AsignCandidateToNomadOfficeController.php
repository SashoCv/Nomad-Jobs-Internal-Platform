<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\AsignCandidateToNomadOffice;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsignCandidateToNomadOfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2){
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
                })->get();

            $candidates = [];

            foreach ($allCandidatesFromAgentForThisCompany as $candidateFromAgent){
                $candidates[]= $candidateFromAgent['candidate'];
            }

            $candidatesIds = array_column($candidates, 'id');

            foreach ($candidatesIds as $candidatesId){
                $assignCandidateToNomadOffice = new AsignCandidateToNomadOffice();
                $assignCandidateToNomadOffice->admin_id = Auth::user()->id;
                $assignCandidateToNomadOffice->nomad_office_id = $nomadOfficeId;
                $assignCandidateToNomadOffice->candidate_id = $candidatesId;
                $assignCandidateToNomadOffice->save();
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
