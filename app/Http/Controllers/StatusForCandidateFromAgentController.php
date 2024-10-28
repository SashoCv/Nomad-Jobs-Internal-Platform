<?php

namespace App\Http\Controllers;

use App\Models\StatusForCandidateFromAgent;
use Illuminate\Http\Request;

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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
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
