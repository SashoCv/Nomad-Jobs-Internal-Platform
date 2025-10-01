<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Statushistory;
use Illuminate\Http\Request;

class StatushistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

        $statusHistory = new Statushistory();

       $statusHistory->candidate_id = $request->candidate_id;
       $statusHistory->status_id = $request->status_id;
       $statusHistory->statusDate = $request->statusDate;



        if ($statusHistory->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $statusHistory
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Statushistory  $statushistory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $statusHistory = Candidate::with('statusHistories')->where('id', '=', $id)->first();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $statusHistory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Statushistory  $statushistory
     * @return \Illuminate\Http\Response
     */
    public function edit(Statushistory $statushistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Statushistory  $statushistory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Statushistory $statushistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the status history and verify it belongs to the specified candidate
        $statusHistory = Statushistory::where('id', $id)
            ->firstOrFail();

        $deletedStatusId = $statusHistory->status_id;

        if ($statusHistory->delete()) {
            // Check if the deleted status was the active status
            $candidate = Candidate::find($candidateId);

            if ($candidate && $candidate->status_id == $deletedStatusId) {
                // Find the next status to set as active (highest order remaining)
                $nextStatusHistory = Statushistory::where('candidate_id', $candidateId)
                    ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
                    ->orderBy('statuses.order', 'desc')
                    ->select('statushistories.*')
                    ->first();

                if ($nextStatusHistory) {
                    $candidate->status_id = $nextStatusHistory->status_id;
                    $candidate->save();
                }
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your Status date has been deleted!',
            ]);
        }
    }
}
