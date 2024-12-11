<?php

namespace App\Http\Controllers;

use App\Models\MedicalInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalInsuranceController extends Controller
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
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days', strtotime($currentDate)));

            $medicalInsurances = MedicalInsurance::with('candidate')
                ->whereBetween('dateTo', [$thirtyDaysAgo, $currentDate])
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MedicalInsurance $medicalInsurance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MedicalInsurance  $medicalInsurance
     * @return \Illuminate\Http\Response
     */
    public function destroy(MedicalInsurance $medicalInsurance)
    {
        //
    }
}
