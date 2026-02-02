<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CandidateContractController extends Controller
{
    /** Company fields to load with contract relationships. */
    private const COMPANY_FIELDS = 'id,nameOfCompany,nameOfCompanyLatin,address,phoneNumber,EIK,EGN,contactPerson,companyCity,dateBornDirector,industry_id,logoPath,logoName,stampPath,stampName,employedByMonths,description,has_owner,director_idCard,director_date_of_issue_idCard,commissionRate';

    public function index(int $candidateId): JsonResponse
    {
        $candidate = Candidate::find($candidateId);

        if (! $candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found',
            ], 404);
        }

        $contracts = CandidateContract::with([
            'company:' . self::COMPANY_FIELDS,
            'position:id,jobPosition,NKDP',
            'status:id,nameOfStatus',
            'type:id,typeOfEmploy',
            'contract_type:id,name,slug',
        ])
            ->where('candidate_id', $candidateId)
            ->orderBy('contract_period_number', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $contract = CandidateContract::with([
            'candidate:id,fullName,fullNameCyrillic,passport,personPicturePath',
            'company:' . self::COMPANY_FIELDS,
            'position:id,jobPosition,NKDP',
            'status:id,nameOfStatus',
            'type:id,typeOfEmploy',
            'contract_type:id,name,slug',
            'companyAddress',
            'agent:id,name,email',
            'user:id,name,email',
            'files',
            'statusHistories.status',
        ])->find($id);

        if (! $contract) {
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

    public function update(Request $request, int $id): JsonResponse
    {
        $contract = CandidateContract::find($id);

        if (! $contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'position_id' => 'sometimes|nullable|exists:positions,id',
            'status_id' => 'sometimes|nullable|exists:statuses,id',
            'type_id' => 'sometimes|nullable|exists:types,id',
            'contract_type' => 'sometimes|nullable|string',
            'contract_type_id' => 'sometimes|nullable|exists:contract_types,id',
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
            'is_extension' => 'sometimes|boolean',
        ]);

        $contract->update($validated);

        if ($contract->is_active) {
            $this->syncContractToCandidate($contract);
        }

        return response()->json([
            'success' => true,
            'data' => $contract->fresh()->load([
                'company:' . self::COMPANY_FIELDS,
                'position:id,jobPosition,NKDP',
                'status:id,nameOfStatus',
                'contract_type:id,name,slug',
            ]),
            'message' => 'Contract updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $contract = CandidateContract::find($id);

        if (! $contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        $contractCount = CandidateContract::where('candidate_id', $contract->candidate_id)->count();

        if ($contractCount <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the only contract for this candidate',
            ], 400);
        }

        if ($contract->is_active) {
            $nextContract = CandidateContract::where('candidate_id', $contract->candidate_id)
                ->where('id', '!=', $id)
                ->orderBy('contract_period_number', 'desc')
                ->first();

            if ($nextContract) {
                $nextContract->update(['is_active' => true]);
                $this->syncContractToCandidate($nextContract);
            }
        }

        $contract->files()->delete();
        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }

    public function expiring(Request $request): JsonResponse
    {
        $months = $request->input('months', 4);

        $contracts = CandidateContract::with([
            'candidate:id,fullName,fullNameCyrillic,passport,personPicturePath',
            'company:' . self::COMPANY_FIELDS,
            'position:id,jobPosition,NKDP',
            'contract_type:id,name,slug',
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

    private function syncContractToCandidate(CandidateContract $contract): void
    {
        $candidate = $contract->candidate;

        if (! $candidate) {
            return;
        }

        $candidate->update([
            'company_id' => $contract->company_id,
            'position_id' => $contract->position_id,
            'status_id' => $contract->status_id,
            'type_id' => $contract->type_id,
            'contractType' => $contract->contract_type,
            'contract_type_id' => $contract->contract_type_id,
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
