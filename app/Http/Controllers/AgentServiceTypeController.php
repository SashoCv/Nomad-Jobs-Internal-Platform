<?php

namespace App\Http\Controllers;

use App\Models\AgentServiceType;
use Illuminate\Http\Request;

class AgentServiceTypeController extends Controller
{
    public function index()
    {
        try {
            $serviceTypes = AgentServiceType::all();
            return response()->json($serviceTypes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve service types: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $serviceType = AgentServiceType::create($validated);

            return response()->json([
                'message' => 'Agent service type created successfully',
                'serviceType' => $serviceType,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create service type: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $serviceType = AgentServiceType::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $serviceType->update($validated);

            return response()->json([
                'message' => 'Agent service type updated successfully',
                'serviceType' => $serviceType,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update service type: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $serviceType = AgentServiceType::findOrFail($id);

            // Check if service type is used in agent_contract_pricing
            $isUsed = \App\Models\AgentContractPricing::where('agent_service_type_id', $id)->exists();

            if ($isUsed) {
                return response()->json([
                    'error' => 'Cannot delete service type',
                    'message' => 'This service type is being used in agent contract pricing and cannot be deleted. You can only edit the name.',
                ], 422);
            }

            $serviceType->delete();

            return response()->json([
                'message' => 'Agent service type deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete service type: ' . $e->getMessage()], 500);
        }
    }
}
