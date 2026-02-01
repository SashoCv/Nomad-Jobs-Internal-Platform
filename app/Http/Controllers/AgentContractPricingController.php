<?php

namespace App\Http\Controllers;

use App\Models\AgentContractPricing;
use App\Models\Permission;
use App\Traits\HasRolePermissions;
use Illuminate\Http\Request;

class AgentContractPricingController extends Controller
{
    use HasRolePermissions;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_READ)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $pricing = AgentContractPricing::with(['agentServiceContract', 'agentServiceType', 'status', 'contractTypes'])
                ->get();

            return response()->json($pricing);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve pricing: ' . $e->getMessage()], 500);
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
        try {
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_CREATE)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $request->validate([
                'agent_service_contract_id' => 'required|exists:agent_service_contracts,id',
                'agent_service_type_id' => 'required|exists:agent_service_types,id',
                'status_id' => 'required|exists:statuses,id',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'countryScopeType' => 'required|in:all,include,exclude',
                'countryScopeIds' => 'nullable|array',
                'countryScopeIds.*' => 'exists:countries,id',
                'companyScopeType' => 'required|in:all,include,exclude',
                'companyScopeIds' => 'nullable|array',
                'companyScopeIds.*' => 'exists:companies,id',
                'contract_type_ids' => 'nullable|array',
                'contract_type_ids.*' => 'exists:contract_types,id',
                'qualification_scope' => 'nullable|in:all,qualified,unqualified',
            ]);

            $pricing = new AgentContractPricing($request->except('contract_type_ids'));
            $pricing->save();

            // Sync contract types (empty array = applies to all)
            if ($request->has('contract_type_ids')) {
                $pricing->contractTypes()->sync($request->contract_type_ids ?? []);
            }

            return response()->json($pricing->load(['agentServiceType', 'status', 'contractTypes']), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create pricing: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_READ)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $pricing = AgentContractPricing::with(['agentServiceContract', 'agentServiceType', 'status', 'contractTypes'])
                ->findOrFail($id);

            return response()->json($pricing);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Pricing not found'], 404);
        }
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
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_UPDATE)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $request->validate([
                'agent_service_contract_id' => 'sometimes|exists:agent_service_contracts,id',
                'agent_service_type_id' => 'sometimes|exists:agent_service_types,id',
                'status_id' => 'sometimes|exists:statuses,id',
                'price' => 'sometimes|numeric|min:0',
                'description' => 'nullable|string',
                'countryScopeType' => 'sometimes|in:all,include,exclude',
                'countryScopeIds' => 'nullable|array',
                'countryScopeIds.*' => 'exists:countries,id',
                'companyScopeType' => 'sometimes|in:all,include,exclude',
                'companyScopeIds' => 'nullable|array',
                'companyScopeIds.*' => 'exists:companies,id',
                'contract_type_ids' => 'nullable|array',
                'contract_type_ids.*' => 'exists:contract_types,id',
                'qualification_scope' => 'nullable|in:all,qualified,unqualified',
            ]);

            $pricing = AgentContractPricing::findOrFail($id);
            $pricing->fill($request->except('contract_type_ids'));
            $pricing->save();

            // Sync contract types if provided (empty array = applies to all)
            if ($request->has('contract_type_ids')) {
                $pricing->contractTypes()->sync($request->contract_type_ids ?? []);
            }

            return response()->json($pricing->load(['agentServiceType', 'status', 'contractTypes']), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update pricing: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_DELETE)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $pricing = AgentContractPricing::findOrFail($id);
            $pricing->delete();

            return response()->json(['message' => 'Pricing deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete pricing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get pricing by contract ID
     *
     * @param  int  $contractId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByContract($contractId)
    {
        try {
            // TODO: Add permission check later
            // if (!$this->checkPermission(Permission::AGENTS_CONTRACTS_READ)) {
            //     return response()->json(['error' => 'Insufficient permissions'], 403);
            // }

            $pricing = AgentContractPricing::with(['agentServiceType', 'status', 'contractTypes'])
                ->where('agent_service_contract_id', $contractId)
                ->get();

            return response()->json($pricing);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve pricing: ' . $e->getMessage()], 500);
        }
    }
}
