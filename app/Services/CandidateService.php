<?php

namespace App\Services;

use App\Jobs\SendEmailForArrivalStatusCandidates;
use App\Models\AgentCandidate;
use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\CandidateContract;
use App\Models\CandidatePassport;
use App\Models\Category;
use App\Models\CompanyJob;
use App\Models\File;
use App\Models\Position;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CandidateService
{
    public function createCandidate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $candidate = new Candidate();
            $data['user_id'] = is_numeric($data['user_id']) ? (int) $data['user_id'] : null;

            // Convert string 'false'/'true' to boolean for boolean fields
            if (isset($data['has_driving_license'])) {
                $data['has_driving_license'] = filter_var($data['has_driving_license'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['is_qualified'])) {
                $data['is_qualified'] = filter_var($data['is_qualified'], FILTER_VALIDATE_BOOLEAN);
            }

            // Filter out passport fields - passport data is stored in candidate_passports table only
            $passportFields = ['passport', 'passportValidUntil', 'passportIssuedBy', 'passportIssuedOn', 'passportPath', 'passportName'];
            $candidateData = array_diff_key($data, array_flip($passportFields));

            $candidate->fill($candidateData);

            $candidate->addedBy = Auth::id();
            $statusId = $data['status_id'] ?? 16; // Default to 'New' status if not provided

            // Calculate derived fields
            $date = isset($data['date']) ? Carbon::parse($data['date']) : Carbon::now();
            $quartal = $candidate->calculateQuartal($date);
            $seasonal = null;

            if (($data['contractType'] ?? '') === Candidate::CONTRACT_TYPE_90_DAYS) {
                $seasonal = $candidate->calculateSeason($date);
            }

            $contractPeriodDate = null;
            if (isset($data['contractPeriod'])) {
                $contractPeriodDate = $candidate->calculateContractEndDate($date, $data['contractPeriod']);
            }

            // Set legacy columns (DUAL WRITE for backward compatibility)
            $candidate->quartal = $quartal;
            $candidate->seasonal = $seasonal;
            $candidate->contractPeriodDate = $contractPeriodDate;

            $candidate->save();

            // Create first contract record (Source of Truth)
            // Note: contract_type mutator automatically sets contract_type_id
            $contract = CandidateContract::create([
                'candidate_id' => $candidate->id,
                'contract_period_number' => 1,
                'is_active' => true,
                'company_id' => $data['company_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'status_id' => $statusId,
                'type_id' => $data['type_id'] ?? Candidate::TYPE_CANDIDATE,
                'contract_type' => $data['contractType'] ?? 'erpr1',
                'contract_period' => $data['contractPeriod'] ?? null,
                'contract_extension_period' => $data['contractExtensionPeriod'] ?? null,
                'start_contract_date' => $data['startContractDate'] ?? null,
                'end_contract_date' => $data['endContractDate'] ?? null,
                'contract_period_date' => $contractPeriodDate,
                'salary' => $data['salary'] ?? null,
                'working_time' => $data['workingTime'] ?? null,
                'working_days' => $data['workingDays'] ?? null,
                'address_of_work' => $data['addressOfWork'] ?? null,
                'name_of_facility' => $data['nameOfFacility'] ?? null,
                'company_adresses_id' => $data['company_adresses_id'] ?? null,
                'dossier_number' => $data['dossierNumber'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'case_id' => $data['case_id'] ?? null,
                'added_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
                'date' => $data['date'] ?? now(),
                'quartal' => $quartal,
                'seasonal' => $seasonal,
                'is_extension' => $data['is_extension'] ?? false,
            ]);

            Log::info('Created new candidate with contract', [
                'candidate_id' => $candidate->id,
                'contract_id' => $contract->id,
            ]);

            // Create calendar event for contract expiry if endContractDate is set
            if (! empty($candidate->endContractDate)) {
                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_CONTRACT_EXPIRY,
                        'candidate_id' => $candidate->id,
                    ],
                    [
                        'title' => 'Изтичащ договор',
                        'date' => Carbon::parse($candidate->endContractDate)->format('Y-m-d'),
                        'company_id' => $candidate->company_id,
                        'created_by' => Auth::id(),
                    ]
                );
            }

            $statusHistory = [
                'candidate_id' => $candidate->id,
                'contract_id' => $contract->id,
                'status_id' => $statusId,
                'statusDate' => Carbon::now()->toDateString(),
                'description' => 'Candidate created',
            ];

            $candidate->statusHistories()->create($statusHistory);
            dispatch(new SendEmailForArrivalStatusCandidates($statusId, $candidate->id, Carbon::now()->toDateString(), false));

            // Handle file uploads
            $this->handleFileUploads($candidate, $data);

            // Create default category
            $this->createDefaultCategory($candidate);

            // Create agent_candidates record if agent_id and company_job_id are provided
            if (! empty($data['agent_id']) && ! empty($data['company_job_id'])) {
                AgentCandidate::create([
                    'user_id' => $data['agent_id'],
                    'company_job_id' => $data['company_job_id'],
                    'candidate_id' => $candidate->id,
                    'contract_id' => $contract->id,
                    'status_for_candidate_from_agent_id' => 3, // Approved status
                    'nomad_office_id' => Auth::user()->id ?? null,
                ]);

                // Check if job posting should be marked as "filled"
                $this->checkAndUpdateJobFilledStatus($data['company_job_id']);
            }

            return [
                'candidate' => $candidate->fresh()->load('activeContract', 'passportRecord'),
                'contract' => $contract,
            ];
        });
    }

    public function updateCandidate(Candidate $candidate, array $data, bool $skipDocumentRegeneration = false, ?int $contractId = null): Candidate
    {
        return DB::transaction(function () use ($candidate, $data, $skipDocumentRegeneration, $contractId) {
            if (! $skipDocumentRegeneration) {
                $this->cleanupAutoGeneratedFiles($candidate, $contractId);
            }

            // Convert string 'false'/'true' to boolean for boolean fields
            if (isset($data['has_driving_license'])) {
                $data['has_driving_license'] = filter_var($data['has_driving_license'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['is_qualified'])) {
                $data['is_qualified'] = filter_var($data['is_qualified'], FILTER_VALIDATE_BOOLEAN);
            }

            // Filter out passport fields - passport data is stored in candidate_passports table only
            $passportFields = ['passport', 'passportValidUntil', 'passportIssuedBy', 'passportIssuedOn', 'passportPath', 'passportName'];
            $candidateData = array_diff_key($data, array_flip($passportFields));

            $candidate->fill($candidateData);

            $candidate->quartal = $candidate->calculateQuartal(Carbon::parse($data['date']));

            if ($data['contractType'] === Candidate::CONTRACT_TYPE_90_DAYS) {
                $candidate->seasonal = $candidate->calculateSeason(Carbon::parse($data['date']));
                Log::info("Candidate seasonal status updated to: {$candidate->seasonal}");
            } else {
                $candidate->seasonal = null;
            }

            if (isset($data['contractPeriod'])) {
                $candidate->contractPeriodDate = $candidate->calculateContractEndDate(
                    Carbon::parse($data['date']),
                    $data['contractPeriod']
                );
            }

            $candidate->save();

            if (! empty($candidate->endContractDate)) {
                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_CONTRACT_EXPIRY,
                        'candidate_id' => $candidate->id,
                    ],
                    [
                        'title' => 'Изтичащ договор',
                        'date' => Carbon::parse($candidate->endContractDate)->format('Y-m-d'),
                        'company_id' => $candidate->company_id,
                    ]
                );
            }

            $this->handleFileUploads($candidate, $data);

            $existingAgentCandidate = AgentCandidate::where('candidate_id', $candidate->id)->first();

            if (! empty($data['agent_id']) && ! empty($data['company_job_id'])) {
                if ($existingAgentCandidate) {
                    $existingAgentCandidate->update([
                        'user_id' => $data['agent_id'],
                        'company_job_id' => $data['company_job_id'],
                        'status_for_candidate_from_agent_id' => 3,
                    ]);
                } else {
                    AgentCandidate::create([
                        'user_id' => $data['agent_id'],
                        'company_job_id' => $data['company_job_id'],
                        'candidate_id' => $candidate->id,
                        'status_for_candidate_from_agent_id' => 3,
                        'nomad_office_id' => Auth::user()->id ?? null,
                    ]);
                }

                $this->checkAndUpdateJobFilledStatus($data['company_job_id']);
            } elseif ($existingAgentCandidate) {
                $existingAgentCandidate->delete();
            }

            return $candidate->load('position', 'passportRecord');
        });
    }

    public function extendCandidateContract(Candidate $candidate, array $data): array
    {
        return DB::transaction(function () use ($candidate, $data) {
            $lastContract = $candidate->latestContract;
            $newPeriodNumber = $lastContract ? $lastContract->contract_period_number + 1 : ($candidate->contractPeriodNumber ?? 0) + 1;

            $candidate->contracts()->update(['is_active' => false]);

            $date = Carbon::parse($data['date']);
            $quartal = $candidate->calculateQuartal($date);
            $seasonal = null;

            if (($data['contractType'] ?? '') === Candidate::CONTRACT_TYPE_90_DAYS) {
                $seasonal = $candidate->calculateSeason($date);
            }

            $contractPeriodDate = null;
            if (isset($data['contractPeriod'])) {
                $contractPeriodDate = $candidate->calculateContractEndDate($date, $data['contractPeriod']);
            }

            // Note: contract_type mutator automatically sets contract_type_id
            $contract = CandidateContract::create([
                'candidate_id' => $candidate->id,
                'contract_period_number' => $newPeriodNumber,
                'is_active' => true,
                'company_id' => $data['company_id'],
                'position_id' => $data['position_id'] ?? null,
                'status_id' => $data['status_id'] ?? null,
                'type_id' => $data['type_id'] ?? Candidate::TYPE_CANDIDATE,
                'contract_type' => $data['contractType'] ?? 'erpr1',
                'contract_period' => $data['contractPeriod'] ?? null,
                'contract_extension_period' => $data['contractExtensionPeriod'] ?? null,
                'start_contract_date' => $data['startContractDate'] ?? null,
                'end_contract_date' => $data['endContractDate'] ?? null,
                'contract_period_date' => $contractPeriodDate,
                'salary' => $data['salary'] ?? null,
                'working_time' => $data['workingTime'] ?? null,
                'working_days' => $data['workingDays'] ?? null,
                'address_of_work' => $data['addressOfWork'] ?? null,
                'name_of_facility' => $data['nameOfFacility'] ?? null,
                'company_adresses_id' => $data['company_adresses_id'] ?? null,
                'dossier_number' => $data['dossierNumber'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'case_id' => $data['case_id'] ?? null,
                'added_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
                'date' => $data['date'] ?? now(),
                'quartal' => $quartal,
                'seasonal' => $seasonal,
                'is_extension' => $data['is_extension'] ?? false,
            ]);

            Log::info('Created new contract', [
                'candidate_id' => $candidate->id,
                'contract_id' => $contract->id,
                'contract_period_number' => $newPeriodNumber
            ]);

            $this->syncContractToLegacyColumns($candidate, $contract, $data);

            if (! empty($data['endContractDate'])) {
                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_CONTRACT_EXPIRY,
                        'candidate_id' => $candidate->id,
                    ],
                    [
                        'title' => 'Изтичащ договор',
                        'date' => Carbon::parse($data['endContractDate'])->format('Y-m-d'),
                        'company_id' => $data['company_id'],
                        'created_by' => Auth::id(),
                    ]
                );
            }

            $this->handleFileUploads($candidate, $data);

            return [
                'candidate' => $candidate->fresh()->load('position', 'activeContract', 'contracts', 'passportRecord'),
                'contract' => $contract,
            ];
        });
    }

    public function findExistingProfile(array $data): ?Candidate
    {
        if (! empty($data['passport']) && strlen(trim($data['passport'])) > 5) {
            $existing = Candidate::whereHas('passportRecord', function ($query) use ($data) {
                $query->where('passport_number', $data['passport']);
            })
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                Log::info('Found existing profile by passport', [
                    'candidate_id' => $existing->id,
                    'passport' => $data['passport']
                ]);
                return $existing;
            }
        }

        if (! empty($data['fullName']) && ! empty($data['birthday'])) {
            $existing = Candidate::where('fullName', $data['fullName'])
                ->where('birthday', $data['birthday'])
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                Log::info('Found existing profile by fullName + birthday', [
                    'candidate_id' => $existing->id,
                    'fullName' => $data['fullName'],
                    'birthday' => $data['birthday']
                ]);
                return $existing;
            }
        }

        return null;
    }

    private function syncContractToLegacyColumns(Candidate $candidate, CandidateContract $contract, array $data): void
    {
        $updateData = [
            'company_id' => $contract->company_id,
            'position_id' => $contract->position_id,
            'status_id' => $contract->status_id,
            'type_id' => $contract->type_id,
            'contractType' => $contract->contract_type,
            'contract_type_id' => $contract->contract_type_id,
            'contractPeriod' => $contract->contract_period,
            'contractPeriodNumber' => $contract->contract_period_number,
            'contractExtensionPeriod' => $contract->contract_extension_period,
            'startContractDate' => $contract->start_contract_date,
            'endContractDate' => $contract->end_contract_date,
            'contractPeriodDate' => $contract->contract_period_date,
            'salary' => $contract->salary,
            'workingTime' => $contract->working_time,
            'workingDays' => $contract->working_days,
            'addressOfWork' => $contract->address_of_work,
            'nameOfFacility' => $contract->name_of_facility,
            'company_adresses_id' => $contract->company_adresses_id,
            'dossierNumber' => $contract->dossier_number,
            'quartal' => $contract->quartal,
            'seasonal' => $contract->seasonal,
            'case_id' => $contract->case_id,
            'agent_id' => $contract->agent_id,
            'user_id' => $contract->user_id,
            'date' => $contract->date,
            'notes' => $contract->notes,
        ];

        // Note: Passport fields removed - passport data is stored in candidate_passports table only
        $personalFields = [
            'fullName', 'fullNameCyrillic', 'email', 'phoneNumber',
            'birthday', 'placeOfBirth', 'nationality', 'gender',
            'address', 'areaOfResidence', 'addressOfResidence', 'periodOfResidence',
            'education', 'specialty', 'qualification', 'martialStatus'
        ];

        foreach ($personalFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $updateData[$field] = $data[$field];
            }
        }

        $candidate->update($updateData);

        Log::info('Synced contract to legacy columns (dual write)', [
            'candidate_id' => $candidate->id,
            'contract_id' => $contract->id
        ]);
    }

    public function promoteToEmployee(Candidate $candidate): bool
    {
        $candidate->promoteToEmployee();
        return true;
    }

    public function deleteCandidate(Candidate $candidate): bool
    {
        return DB::transaction(function () use ($candidate) {
            $userId = Auth::id();
            $files = File::where('candidate_id', $candidate->id)->get();

            foreach ($files as $file) {
                if ($file->filePath && Storage::disk('public')->exists($file->filePath)) {
                    Storage::disk('public')->delete($file->filePath);
                }
                $file->delete();
            }

            Category::where('candidate_id', $candidate->id)->delete();

            $agentCandidate = AgentCandidate::where('candidate_id', $candidate->id)->first();
            if ($agentCandidate) {
                $agentCandidate->deleted_by = $userId;
                $agentCandidate->save();
                $agentCandidate->delete();
            }

            $candidate->deleted_by = $userId;
            $candidate->save();

            return $candidate->delete();
        });
    }

    public function updateSeasonalForAllCandidates(): int
    {
        $candidates = Candidate::seasonalContracts()->get();
        $updated = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->date) {
                $candidate->seasonal = $candidate->calculateSeason($candidate->date);
                $candidate->save();
                $updated++;
            }
        }

        return $updated;
    }

    public function updateQuartalForAllCandidates(): int
    {
        $candidates = Candidate::all();
        $updated = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->date) {
                $candidate->quartal = $candidate->calculateQuartal($candidate->date);
                $candidate->save();
                $updated++;
            }
        }

        return $updated;
    }

    public function updateAddedByForAllCandidates(): int
    {
        $candidates = Candidate::whereNull('addedBy')->get();
        $updated = 0;

        foreach ($candidates as $candidate) {
            $candidate->addedBy = $candidate->user_id ?? 11; // Default admin user
            $candidate->save();
            $updated++;
        }

        return $updated;
    }

    public function getExpiringContracts(int $monthsAhead = 4): \Illuminate\Database\Eloquent\Builder
    {
        $futureDate = Carbon::now()->addMonths($monthsAhead);

        return Candidate::with(['company:id,nameOfCompany,EIK', 'status:id,nameOfStatus', 'position:id,jobPosition'])
            ->contractExpiring($futureDate)
            ->orderBy('endContractDate', 'asc');
    }

    public function getFirstQuartal(): ?string
    {
        $candidate = Candidate::whereNotNull('quartal')
            ->orderByRaw('CAST(SUBSTRING_INDEX(quartal, "/", -1) AS UNSIGNED)')
            ->orderByRaw('CAST(SUBSTRING_INDEX(quartal, "/", 1) AS UNSIGNED)')
            ->first();

        return $candidate?->quartal;
    }

    protected function handleFileUploads(Candidate $candidate, array $data): void
    {
        $this->syncPassportData($candidate, $data);

        if (isset($data['personPicture'])
            && $data['personPicture'] instanceof UploadedFile
            && $data['personPicture']->isValid()
            && $data['personPicture']->getSize() > 0
        ) {
            $picturePath = $data['personPicture']->store('personImages', 'public');
            $candidate->update([
                'personPicturePath' => $picturePath,
                'personPictureName' => $data['personPicture']->getClientOriginalName()
            ]);
        }
    }

    protected function syncPassportData(Candidate $candidate, array $data): void
    {
        $passportPath = null;
        $passportFileName = null;

        // Handle passport file upload - store only in candidate_passports table
        if (isset($data['personPassport'])
            && $data['personPassport'] instanceof UploadedFile
            && $data['personPassport']->isValid()
            && $data['personPassport']->getSize() > 0
        ) {
            $directory = 'candidate/' . $candidate->id . '/passport';
            $fileName = \Illuminate\Support\Str::uuid() . '_' . $data['personPassport']->getClientOriginalName();
            $passportPath = $data['personPassport']->storeAs($directory, $fileName, 'public');
            $passportFileName = $data['personPassport']->getClientOriginalName();
        }

        $hasPassportData = ! empty($data['passport'])
            || ! empty($data['passportValidUntil'])
            || ! empty($data['passportIssuedOn'])
            || ! empty($data['passportIssuedBy'])
            || $passportPath !== null;

        if (! $hasPassportData) {
            return;
        }

        $passportData = [];

        // Only include non-empty values to avoid overwriting existing data
        if (! empty($data['passport'])) {
            $passportData['passport_number'] = $data['passport'];
        }
        if (! empty($data['passportValidUntil'])) {
            $passportData['expiry_date'] = $data['passportValidUntil'];
        }
        if (! empty($data['passportIssuedOn'])) {
            $passportData['issue_date'] = $data['passportIssuedOn'];
        }
        if (! empty($data['passportIssuedBy'])) {
            $passportData['issued_by'] = $data['passportIssuedBy'];
        }

        if ($passportPath !== null) {
            $passportData['file_path'] = $passportPath;
            $passportData['file_name'] = $passportFileName;
        }

        // Only update if we have data to update
        if (empty($passportData)) {
            return;
        }

        CandidatePassport::updateOrCreate(
            ['candidate_id' => $candidate->id],
            $passportData
        );

        Log::info('Synced passport data to candidate_passports', [
            'candidate_id' => $candidate->id,
            'has_file' => $passportPath !== null,
        ]);
    }

    protected function createDefaultCategory(Candidate $candidate): void
    {
        foreach (\App\Enums\DefaultCandidateCategory::cases() as $category) {
            $def = $category->definition();

            Category::create([
                'candidate_id' => $candidate->id,
                'nameOfCategory' => $def->name,
                'description'   => $def->description,
                'role_id'       => $def->roleId,
                'isGenerated'   => $def->isGenerated,
            ]);
        }
    }

    protected function cleanupAutoGeneratedFiles(Candidate $candidate, ?int $contractId = null): void
    {
        $query = File::where('candidate_id', $candidate->id)
            ->where('autoGenerated', 1)
            ->where('deleteFile', 0);

        // If contract_id is provided, only delete files for that specific contract
        if ($contractId !== null) {
            $query->where('contract_id', $contractId);
        }

        $files = $query->get();

        foreach ($files as $file) {
            if ($file->filePath && Storage::disk('public')->exists($file->filePath)) {
                Storage::disk('public')->delete($file->filePath);
            }
            $file->delete();
        }
    }

    protected function checkAndUpdateJobFilledStatus(int $companyJobId): void
    {
        $companyJob = CompanyJob::find($companyJobId);

        if (! $companyJob) {
            return;
        }

        if (! in_array($companyJob->status, ['active', 'inactive'])) {
            return;
        }

        $approvedCount = AgentCandidate::where('company_job_id', $companyJobId)
            ->where('status_for_candidate_from_agent_id', 3)
            ->whereNull('deleted_at')
            ->count();

        if ($approvedCount >= $companyJob->number_of_positions) {
            $companyJob->status = 'filled';
            $companyJob->save();
        }
    }
}
