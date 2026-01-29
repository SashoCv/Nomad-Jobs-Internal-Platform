<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CandidateContractController extends Controller
{
    /**
     * Get all contracts for a candidate
     *
     * @param int $candidateId
     * @return JsonResponse
     */
    public function index(int $candidateId): JsonResponse
    {
        $candidate = Candidate::find($candidateId);

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found',
            ], 404);
        }

        $contracts = CandidateContract::with([
            'company:id,nameOfCompany,EIK',
            'position:id,jobPosition,NKDP',
            'status:id,nameOfStatus',
            'type:id,typeOfEmploy',
        ])
            ->where('candidate_id', $candidateId)
            ->orderBy('contract_period_number', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Get single contract details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $contract = CandidateContract::with([
            'candidate:id,fullName,fullNameCyrillic,passport,personPicturePath',
            'company:id,nameOfCompany,EIK',
            'position:id,jobPosition,NKDP',
            'status:id,nameOfStatus',
            'type:id,typeOfEmploy',
            'companyAddress',
            'agent:id,name,email',
            'user:id,name,email',
            'files',
            'statusHistories.status',
        ])->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    /**
     * Update a contract
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contract = CandidateContract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'position_id' => 'sometimes|nullable|exists:positions,id',
            'status_id' => 'sometimes|nullable|exists:statuses,id',
            'type_id' => 'sometimes|nullable|exists:types,id',
            'contract_type' => 'sometimes|string',
            'contract_period' => 'sometimes|nullable|string',
            'start_contract_date' => 'sometimes|nullable|date',
            'end_contract_date' => 'sometimes|nullable|date',
            'salary' => 'sometimes|nullable|numeric',
            'working_time' => 'sometimes|nullable|string',
            'working_days' => 'sometimes|nullable|string',
            'address_of_work' => 'sometimes|nullable|string',
            'name_of_facility' => 'sometimes|nullable|string',
            'dossier_number' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
            'company_adresses_id' => 'sometimes|nullable|exists:company_adresses,id',
            'contract_extension_period' => 'sometimes|nullable|string',
            'seasonal' => 'sometimes|nullable|string',
            'quartal' => 'sometimes|nullable|string',
            'user_id' => 'sometimes|nullable|exists:users,id',
            'agent_id' => 'sometimes|nullable|exists:users,id',
            'case_id' => 'sometimes|nullable|exists:cases,id',
            'company_job_id' => 'sometimes|nullable|integer',
        ]);

        $contract->update($validated);

        // If this is the active contract, also update the candidate's legacy columns
        if ($contract->is_active) {
            $candidate = $contract->candidate;
            if ($candidate) {
                $candidate->update([
                    'company_id' => $contract->company_id,
                    'position_id' => $contract->position_id,
                    'status_id' => $contract->status_id,
                    'type_id' => $contract->type_id,
                    'contractType' => $contract->contract_type,
                    'contractPeriod' => $contract->contract_period,
                    'startContractDate' => $contract->start_contract_date,
                    'endContractDate' => $contract->end_contract_date,
                    'salary' => $contract->salary,
                    'workingTime' => $contract->working_time,
                    'workingDays' => $contract->working_days,
                    'addressOfWork' => $contract->address_of_work,
                    'nameOfFacility' => $contract->name_of_facility,
                    'dossierNumber' => $contract->dossier_number,
                    'company_adresses_id' => $contract->company_adresses_id,
                    'notes' => $contract->notes,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $contract->fresh()->load([
                'company:id,nameOfCompany',
                'position:id,jobPosition',
                'status:id,nameOfStatus',
            ]),
            'message' => 'Contract updated successfully',
        ]);
    }

    /**
     * Delete a contract
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $contract = CandidateContract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        // Check if this is the only contract for the candidate
        $contractCount = CandidateContract::where('candidate_id', $contract->candidate_id)->count();
        if ($contractCount <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the only contract for this candidate',
            ], 400);
        }

        // If deleting the active contract, make another one active
        if ($contract->is_active) {
            $nextContract = CandidateContract::where('candidate_id', $contract->candidate_id)
                ->where('id', '!=', $id)
                ->orderBy('contract_period_number', 'desc')
                ->first();

            if ($nextContract) {
                $nextContract->update(['is_active' => true]);

                // Update candidate's legacy columns to reflect the new active contract
                $candidate = $nextContract->candidate;
                if ($candidate) {
                    $candidate->update([
                        'company_id' => $nextContract->company_id,
                        'position_id' => $nextContract->position_id,
                        'status_id' => $nextContract->status_id,
                        'type_id' => $nextContract->type_id,
                        'contractType' => $nextContract->contract_type,
                        'contractPeriod' => $nextContract->contract_period,
                        'startContractDate' => $nextContract->start_contract_date,
                        'endContractDate' => $nextContract->end_contract_date,
                        'salary' => $nextContract->salary,
                        'workingTime' => $nextContract->working_time,
                        'workingDays' => $nextContract->working_days,
                        'addressOfWork' => $nextContract->address_of_work,
                        'nameOfFacility' => $nextContract->name_of_facility,
                        'dossierNumber' => $nextContract->dossier_number,
                        'company_adresses_id' => $nextContract->company_adresses_id,
                        'notes' => $nextContract->notes,
                    ]);
                }
            }
        }

        // Delete related files first
        $contract->files()->delete();

        // Delete the contract
        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }

    /**
     * Get expiring contracts (contracts expiring within specified months)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function expiring(Request $request): JsonResponse
    {
        $months = $request->input('months', 4);

        $contracts = CandidateContract::with([
            'candidate:id,fullName,fullNameCyrillic,passport,personPicturePath',
            'company:id,nameOfCompany,EIK',
            'position:id,jobPosition,NKDP',
        ])
            ->expiringSoon($months)
            ->orderBy('end_contract_date', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $contracts,
        ]);
    }
}
