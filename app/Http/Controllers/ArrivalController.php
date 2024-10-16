<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArrivalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $arrivals = Arrival::with(['company', 'candidate'])->get();
            } else {
                $arrivals = []; // Here i need to implement the logic to get the arrivals for the Company
            }

            return response()->json([
                'message' => 'Arrivals retrieved successfully',
                'arrivals' => $arrivals
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            try {
                $arrival = new Arrival();

                $arrival->company_id = $request->company_id;
                $arrival->candidate_id = $request->candidate_id;
                $arrival->arrival_date =  Carbon::createFromFormat('m-d-Y',$request->arrival_date)->format('Y-m-d');
                $arrival->arrival_time = $request->arrival_time;
                $arrival->arrival_location = $request->arrival_location;
                $arrival->arrival_flight = $request->arrival_flight;
                $arrival->where_to_stay = $request->where_to_stay;

                if ($arrival->save()) {
                    $arrivalCandidate = new ArrivalCandidate();

                    $arrivalCandidate->arrival_id = $arrival->id;
                    $arrivalCandidate->status_arrival_id = 1;
                    $arrivalCandidate->status_description = 'Arrival created';
                    $arrivalCandidate->status_date = $request->arrival_date;

                    $arrivalCandidate->save();
                }

                return response()->json([
                    'message' => 'Arrival created successfully',
                    'arrival' => $arrival
                ]);
            } catch (\Exception $e) {
                return response()->json($e->getMessage());
            }
        }
    }


    public function destroy($id): JsonResponse
    {
        if(Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
            return response()->json('You are not authorized to perform this action');
        }

        try {
            $arrival = Arrival::find($id);
            $arrival->delete();

            return response()->json('Arrival deleted successfully');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
