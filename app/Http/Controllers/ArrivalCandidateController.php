<?php

namespace App\Http\Controllers;

use App\Models\ArrivalCandidate;
use Illuminate\Http\Request;

class ArrivalCandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $statusId = $request->status_id;
            $fromDate = $request->from_date;
            $toDate = $request->to_date;

            $query = ArrivalCandidate::with(['arrival.candidate', 'statusArrival']);

            if ($statusId) {
                $query->where('status_arrival_id', $statusId);
            }

            if($fromDate && $toDate) {
                $query->whereBetween('status_date', [$fromDate, $toDate]);
            }

            $query->orderBy('status_date', 'asc');

            $arrivalCandidates = $query->paginate();

            return response()->json([
                'message' => 'Arrival Candidates retrieved successfully',
                'arrivalCandidates' => $arrivalCandidates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving arrival candidates.',
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $arrivalCandidate = new ArrivalCandidate();
            $arrivalCandidate->arrival_id = $request->arrival_id;
            $arrivalCandidate->status_arrival_id = $request->status_arrival_id;
            $arrivalCandidate->status_description = $request->status_description;
            $arrivalCandidate->status_date = $request->status_date;

            $arrivalCandidate->save();

            return response()->json([
                'message' => 'Arrival Candidate created successfully',
                'arrivalCandidate' => $arrivalCandidate
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\Response
     */
    public function show(ArrivalCandidate $arrivalCandidate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\Response
     */
    public function edit(ArrivalCandidate $arrivalCandidate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $arrivalCandidate = ArrivalCandidate::find($request->id);
            $arrivalCandidate->status_arrival_id = $request->status_arrival_id;
            $arrivalCandidate->status_description = $request->status_description;
            $arrivalCandidate->status_date = $request->status_date;

            $arrivalCandidate->save();

            return response()->json([
                'message' => 'Arrival Candidate updated successfully',
                'arrivalCandidate' => $arrivalCandidate
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $arrivalCandidate = ArrivalCandidate::find($id);
            $arrivalCandidate->delete();

            return response()->json('Arrival Candidate deleted successfully');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
