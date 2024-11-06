<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\StatusForCandidateFromAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusForCandidateFromAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $statusForCandidateFromAgent = StatusForCandidateFromAgent::all(['id', 'name']);
            return response()->json($statusForCandidateFromAgent);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function show(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function edit(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
           $candidateFromAgent = AgentCandidate::where('candidate_id', $id)->first();
              if ($candidateFromAgent) {
                $candidateFromAgent->status_for_candidate_from_agent_id = $request->status_for_candidate_from_agent_id;
                if($request->status_for_candidate_from_agent_id == 3){
                    $updateTypeOfCandidate = Candidate::where('id', $id)->first();
                    $updateTypeOfCandidate->type_id = 1;
                    $updateTypeOfCandidate->save();
                }
                $candidateFromAgent->save();
                return response()->json(['message' => 'Status updated successfully'], 200);
              } else {
                return response()->json(['message' => 'Candidate not found'], 404);
              }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function destroy(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }
}
