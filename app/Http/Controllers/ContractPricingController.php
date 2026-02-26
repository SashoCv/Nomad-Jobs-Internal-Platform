<?php

namespace App\Http\Controllers;

use App\Models\ContractPricing;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ContractPricingController extends Controller
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
            $data = $request->all();

            // ако праќа директно array
            if (!is_array($data)) {
                return response()->json(['error' => 'Invalid data format. Expected array.'], 422);
            }

            // валидација на секој item
            foreach ($data as $item) {
                $validator = Validator::make($item, [
                    'company_service_contract_id' => 'required|exists:company_service_contracts,id',
                    'contract_service_type_id' => 'required|exists:contract_service_types,id',
                    'price' => 'required|numeric|min:0',
                    'status_id' => 'required|exists:statuses,id',
                    'description' => 'nullable|string|max:255',
                    'country_scope_type' => 'nullable|in:all,include,exclude',
                    'country_scope_ids' => 'nullable|array',
                    'country_scope_ids.*' => 'exists:countries,id',
                ]);

                if ($validator->fails()) {
                    return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
                }
            }

            // сними ги сите
            $created = [];
            foreach ($data as $item) {
                $created[] = ContractPricing::create($item);
            }

            return response()->json($created, 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create contract pricing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ContractPricing  $contractPricing
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $contractPricing = ContractPricing::with(['companyServiceContract', 'contractServiceType', 'status'])
                ->findOrFail($id);

            return response()->json($contractPricing);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve contract pricing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ContractPricing  $contractPricing
     * @return \Illuminate\Http\Response
     */
    public function edit(ContractPricing $contractPricing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $contractPricing = ContractPricing::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'contract_service_type_id' => 'required|exists:contract_service_types,id',
                'price' => 'required|numeric|min:0',
                'status_id' => 'required|exists:statuses,id',
                'description' => 'nullable|string|max:255',
                'country_scope_type' => 'nullable|in:all,include,exclude',
                'country_scope_ids' => 'nullable|array',
                'country_scope_ids.*' => 'exists:countries,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
            }

            $contractPricing->update($request->all());

            return response()->json([
                'message' => 'Contract pricing updated successfully.',
                'data' => $contractPricing->fresh(['contractServiceType', 'status'])
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update contract pricing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContractPricing  $contractPricing
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $contractPricing = ContractPricing::findOrFail($id);
            $contractPricing->delete();

            return response()->json(['message' => 'Contract pricing deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete contract pricing: ' . $e->getMessage()], 500);
        }
    }
}
