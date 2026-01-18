<?php

namespace App\Http\Controllers;

use App\Models\ContractServiceType;
use Illuminate\Http\Request;

class ContractServiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $contractServiceTypes = ContractServiceType::all('id', 'name', 'catalog_number');
            return response()->json($contractServiceTypes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve contract service types: ' . $e->getMessage()], 500);
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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'catalog_number' => 'nullable|string|max:255|unique:contract_service_types,catalog_number',
            ]);

            $serviceType = ContractServiceType::create($validated);

            return response()->json([
                'message' => 'Contract service type created successfully',
                'serviceType' => $serviceType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create service type: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $serviceType = ContractServiceType::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'catalog_number' => 'nullable|string|max:255|unique:contract_service_types,catalog_number,' . $id,
            ]);

            $serviceType->update($validated);

            return response()->json([
                'message' => 'Contract service type updated successfully',
                'serviceType' => $serviceType,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update service type: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $serviceType = ContractServiceType::findOrFail($id);

            // Check if service type is used in invoices
            $isUsedInInvoices = \App\Models\Invoice::where('contract_service_type_id', $id)->exists();

            if ($isUsedInInvoices) {
                return response()->json([
                    'error' => 'Не може да се изтрие този тип услуга, защото се използва във фактури.',
                ], 422);
            }

            // Check if service type is used in contract pricings
            $isUsedInPricings = \App\Models\ContractPricing::where('contract_service_type_id', $id)->count();

            if ($isUsedInPricings > 0) {
                return response()->json([
                    'error' => 'Не може да се изтрие този тип услуга, защото се използва в ценообразуване на договори.',
                ], 422);
            }

            $serviceType->delete();

            return response()->json([
                'message' => 'Contract service type deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete service type: ' . $e->getMessage()], 500);
        }
    }
}
