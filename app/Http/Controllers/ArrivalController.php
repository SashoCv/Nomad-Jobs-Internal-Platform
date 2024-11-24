<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailForArrivalCandidates;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            try {
                DB::beginTransaction();

                $candidateId = $request->candidate_id;
                $arrival = Arrival::firstOrNew(['candidate_id' => $candidateId]);

                $arrival->fill([
                    'company_id' => $request->company_id,
                    'arrival_date' => Carbon::createFromFormat('m-d-Y', $request->arrival_date)->format('Y-m-d'),
                    'arrival_time' => $request->arrival_time,
                    'arrival_location' => $request->arrival_location,
                    'arrival_flight' => $request->arrival_flight,
                    'where_to_stay' => $request->where_to_stay,
                    'phone_number' => $request->phone_number,
                ]);

                if (!$arrival->save()) {
                    throw new \Exception('Failed to save arrival details.');
                }

                $arrivalCandidate = ArrivalCandidate::firstOrNew(['arrival_id' => $arrival->id]);
                $arrivalCandidate->fill([
                    'arrival_id' => $arrival->id,
                    'status_arrival_id' => 8,
                    'status_description' => 'Arrival Expected',
                    'status_date' => Carbon::parse($arrival->arrival_date)->format('m-d-Y'),
                ]);

                if(!$arrivalCandidate->save()) {
                    throw new \Exception('Failed to save arrival candidate details.');
                }

                $existingCategory = Category::firstOrCreate(
                    [
                        'candidate_id' => $candidateId,
                        'nameOfCategory' => 'Documents For Arrival Candidates',
                    ],
                    [
                        'role_id' => 2,
                        'isGenerated' => 0,
                    ]
                );

                dispatch(new SendEmailForArrivalCandidates($arrival, $arrivalCandidate->status_arrival_id));

                DB::commit();

                return response()->json([
                    'message' => 'Arrival created successfully',
                    'arrival' => $arrival,
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'You are not authorized to create an arrival'], 403);
    }



    public function update(Request $request, $id)
    {
        try {
            $arrival = Arrival::find($id);

            $arrival->company_id = $request->company_id;
            $arrival->candidate_id = $request->candidate_id;
            $arrival->arrival_date =  Carbon::createFromFormat('m-d-Y',$request->arrival_date)->format('Y-m-d');
            $arrival->arrival_time = $request->arrival_time;
            $arrival->arrival_location = $request->arrival_location;
            $arrival->arrival_flight = $request->arrival_flight;
            $arrival->where_to_stay = $request->where_to_stay;
            $arrival->phone_number = $request->phone_number;  // new field

            $arrival->save();

            return response()->json([
                'message' => 'Arrival updated successfully',
                'arrival' => $arrival
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    public function destroy($id): JsonResponse
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            try {
                $arrivalCandidates = ArrivalCandidate::where('arrival_id', $id)->first();
                $arrivalCandidates->delete();

                $arrival = Arrival::find($id);
                $arrival->delete();

                return response()->json('Arrival deleted successfully');
            } catch (\Exception $e) {
                return response()->json($e->getMessage());
            }
        } else {
            return response()->json('You are not authorized to delete this arrival');
        }
    }
}
