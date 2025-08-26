<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArrivalPricingController extends Controller
{

    public function storeTransportCoverBy(Request $request, $arrivalId)
    {
        try {
            $arrival = Arrival::with('arrivalPricing')->where('id', $arrivalId)->firstOrFail();

            $data = $request->validate([
                'isTransportCoveredByNomad' => 'required|boolean',
            ]);

            $data['arrival_id'] = $arrival->id;
            $data['billed'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->billed : false;
            $data['price'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->price : null;
            $data['margin'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->margin : null;
            $data['total'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->total : null;

            $arrivalPricing = ArrivalPricing::updateOrCreate(
                ['arrival_id' => $arrival->id],
                $data
            );

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $arrivalPricing
            ]);
        } catch (\Exception $e) {
            Log::info('Error updating transport cover: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error updating transport cover: ' . $e->getMessage()
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
            $data = $request->validate([
                'arrival_id' => 'required|exists:arrivals,id',
                'price' => 'required|numeric',
                'margin' => 'required|numeric',
                'total' => 'required|numeric',
            ]);

            $arrivalPricing = ArrivalPricing::updateOrCreate(
                ['arrival_id' => $data['arrival_id']],
                [
                    'price' => $data['price'],
                    'margin' => $data['margin'],
                    'total' => $data['total'],
                    'billed' => false,
                    'isTransportCoveredByNomad' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => $arrivalPricing
            ]);
        } catch (\Exception $e) {
            Log::info('Error creating arrival pricing: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error creating arrival pricing: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function show(ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function edit(ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function destroy(ArrivalPricing $arrivalPricing)
    {
        //
    }
}
