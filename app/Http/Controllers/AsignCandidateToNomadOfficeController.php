<?php

namespace App\Http\Controllers;

use App\Models\AsignCandidateToNomadOffice;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsignCandidateToNomadOfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            if(Auth::user()->role_id == 1){
                $candidatesAddByAgent = Candidate::with(['company', 'status', 'position', 'user','cases','agentCandidates'])
               ->whereHas('agentCandidates')->get();
            } else {
                $candidatesAddByAgent = Candidate::with(['company', 'status', 'position', 'user','cases','agentCandidates','asignCandidateToNomadOffice'])
               ->whereHas('asignCandidateToNomadOffice', function($query){
                   $query->where('nomad_office_id', Auth::user()->id);
               })->get();
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
        if(Auth::user()->role_id != 1){
            return response()->json([
                'message' => 'You are not authorized to assign candidate to nomad office'
            ], 401);
        }

        $request->validate([
            'nomad_office_id' => 'required',
            'candidate_id' => 'required',
        ]);

        $asignCandidateToNomadOffice = new AsignCandidateToNomadOffice();
        $asignCandidateToNomadOffice->admin_id = Auth::user()->id;
        $asignCandidateToNomadOffice->nomad_office_id = $request->nomad_office_id;
        $asignCandidateToNomadOffice->candidate_id = $request->candidate_id;


        if($asignCandidateToNomadOffice->save()){

            $candidate = Candidate::find($request->candidate_id);
            $candidate->type_id = 1;
            $candidate->save();

            return response()->json([
                'message' => 'Candidate assigned to nomad office successfully',
                'data' => $asignCandidateToNomadOffice
            ], 200);
        }else{
            return response()->json([
                'message' => 'Failed to assign candidate to nomad office'
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
