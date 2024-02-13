<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Candidate;


class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
          $statuses = Status::all();
        
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
     * @return \Illuminate\Http\Response
     */
    public function updateStatusForCandidate(Request $request)
    {

        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $idForCandidate = $request->candidate_id;
            $changedStatus = $request->status_id;

            $candidate = Candidate::where('id', $idForCandidate)->first();
            $candidate->status_id = $changedStatus;


            if ($candidate->save()) {
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
