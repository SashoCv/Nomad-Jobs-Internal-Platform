<?php

namespace App\Http\Controllers;

use App\Models\MedicalInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalInsuranceController extends Controller
{
    public function index($id)
    {
        try {
            $medicalInsurances = MedicalInsurance::where('candidate_id', $id)->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurances
            ]);
        } catch (\Exception $e) {
            Log::info('Medical Insurance fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
        }
    }
    public function showForCandidate($id)
    {
        try {
            $medicalInsurance = MedicalInsurance::where('id', $id)->first();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurance
            ]);
        } catch (\Exception $e) {
            Log::info('Medical Insurance fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
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
            $medicalInsurance = new MedicalInsurance();
            $medicalInsurance->name = $request->name;
            $medicalInsurance->description = $request->description;
            $medicalInsurance->candidate_id = $request->candidate_id;
            $medicalInsurance->dateFrom = $request->dateFrom;
            $medicalInsurance->dateTo = $request->dateTo;

            if($medicalInsurance->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Medical Insurance created successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'message' => 'Medical Insurance creation failed'
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Medical Insurance creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance creation failed'
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MedicalInsurance  $medicalInsurance
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        try {
            $currentDate = date('Y-m-d');
            $thirtyDaysAgo = date('Y-m-d', strtotime('+30 days', strtotime($currentDate)));

            $medicalInsurances = MedicalInsurance::with('candidate')
                ->where('dateTo', '<=', $thirtyDaysAgo)
                ->paginate();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurances
            ]);
        } catch (\Exception $e) {
            Log::info('Medical Insurance fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MedicalInsurance  $medicalInsurance
     * @return \Illuminate\Http\Response
     */
    public function edit(MedicalInsurance $medicalInsurance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MedicalInsurance  $medicalInsurance
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $medicalInsurance = MedicalInsurance::where('id', $id)->first();
            $medicalInsurance->name = $request->name;
            $medicalInsurance->description = $request->description;
            $medicalInsurance->candidate_id = $request->candidate_id;
            $medicalInsurance->dateFrom = $request->dateFrom;
            $medicalInsurance->dateTo = $request->dateTo;

            if($medicalInsurance->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Medical Insurance updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'message' => 'Medical Insurance update failed'
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Medical Insurance update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance update failed'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MedicalInsurance  $medicalInsurance
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $medicalInsurance = MedicalInsurance::where('id', $id)->first();

            if($medicalInsurance->delete()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Medical Insurance deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'message' => 'Medical Insurance deletion failed'
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Medical Insurance deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance deletion failed'
            ]);
        }
    }
}
