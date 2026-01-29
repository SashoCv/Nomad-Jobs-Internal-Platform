<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration:
     * 1. Groups candidates by passport or fullName+birthday
     * 2. Selects a master profile for each group (Employee first, then oldest)
     * 3. Creates contract records for all candidates
     * 4. Re-links satellite tables (files, statushistories, invoices, etc.)
     * 5. Dual-writes active contract data to master profile
     * 6. Soft-deletes duplicate profiles
     */
    public function up(): void
    {
        // Get IDs of candidates that already have contract records (created via new flow)
        $candidatesWithContracts = DB::table('candidate_contracts')
            ->pluck('candidate_id')
            ->unique()
            ->toArray();

        // Get all non-deleted candidates that DON'T already have contracts
        $allCandidates = DB::table('candidates')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $candidatesWithContracts)
            ->orderBy('created_at', 'asc')
            ->get();

        Log::info('Starting candidate migration', [
            'total_candidates' => $allCandidates->count(),
            'skipped_with_contracts' => count($candidatesWithContracts),
        ]);

        // Step 1: Group candidates by "physical person"
        $personGroups = [];

        foreach ($allCandidates as $candidate) {
            $key = $this->getPersonKey($candidate);
            if (!isset($personGroups[$key])) {
                $personGroups[$key] = [];
            }
            $personGroups[$key][] = $candidate;
        }

        Log::info('Grouped candidates into persons', ['person_count' => count($personGroups)]);

        // Step 2 & 3: Process each person group
        foreach ($personGroups as $key => $candidates) {
            $this->processPersonGroup($candidates);
        }

        Log::info('Migration completed successfully');
    }

    /**
     * Generate a unique key for a physical person
     */
    private function getPersonKey($candidate): string
    {
        // Priority 1: Match by passport (if valid/non-empty)
        if (!empty($candidate->passport) && strlen(trim($candidate->passport)) > 5) {
            return 'passport:' . strtoupper(trim($candidate->passport));
        }

        // Priority 2: Match by fullName + birthday
        $name = strtolower(trim($candidate->fullName ?? ''));
        $birthday = $candidate->birthday ?? 'unknown';

        return 'name_dob:' . $name . '|' . $birthday;
    }

    /**
     * Process a group of candidates belonging to the same physical person
     */
    private function processPersonGroup(array $candidates): void
    {
        // Sort by: Employee (type_id=2) first, then by created_at ASC
        usort($candidates, function ($a, $b) {
            if ($a->type_id == 2 && $b->type_id != 2) return -1;
            if ($b->type_id == 2 && $a->type_id != 2) return 1;
            return strtotime($a->created_at) <=> strtotime($b->created_at);
        });

        $masterProfile = $candidates[0];
        $latestCandidate = end($candidates);

        // Track mapping for re-linking
        $candidateToContractMap = [];

        // Create contract records for ALL candidates in the group
        // Use sequential period numbers to avoid duplicates
        $periodNumber = 1;
        foreach ($candidates as $candidate) {
            $contractId = $this->createContractRecord($candidate, $masterProfile->id, $candidate->id === $latestCandidate->id, $periodNumber);
            $candidateToContractMap[$candidate->id] = $contractId;
            $periodNumber++;
        }

        // Re-link satellite tables for duplicate candidates
        foreach ($candidates as $candidate) {
            if ($candidate->id !== $masterProfile->id) {
                $this->relinkSatelliteTables($candidate->id, $masterProfile->id, $candidateToContractMap[$candidate->id]);

                // Soft-delete the duplicate profile
                DB::table('candidates')
                    ->where('id', $candidate->id)
                    ->update(['deleted_at' => Carbon::now()]);
            } else {
                // For master profile, just add contract_id to satellite records
                $this->addContractIdToMasterRecords($masterProfile->id, $candidateToContractMap[$masterProfile->id]);
            }
        }

        // DUAL WRITE: Sync active contract data to master profile's legacy columns
        $activeContractId = $candidateToContractMap[$latestCandidate->id];
        $this->syncActiveContractToLegacyColumns($masterProfile->id, $activeContractId);
    }

    /**
     * Create a contract record for a candidate
     */
    private function createContractRecord($candidate, int $masterProfileId, bool $isActive, int $periodNumber): int
    {
        $now = Carbon::now();

        $contractId = DB::table('candidate_contracts')->insertGetId([
            'candidate_id' => $masterProfileId,
            'company_id' => $candidate->company_id ?? 1, // Default to 1 if null (should not happen in practice)
            'position_id' => $candidate->position_id,
            'status_id' => $candidate->status_id,
            'type_id' => $candidate->type_id,
            'contract_type' => $candidate->contractType ?? 'indefinite',
            'contract_period' => $candidate->contractPeriod,
            'contract_period_number' => $periodNumber,
            'contract_extension_period' => $candidate->contractExtensionPeriod,
            'start_contract_date' => $candidate->startContractDate,
            'end_contract_date' => $candidate->endContractDate,
            'contract_period_date' => $candidate->contractPeriodDate,
            'salary' => $candidate->salary,
            'working_time' => $candidate->workingTime,
            'working_days' => $candidate->workingDays,
            'address_of_work' => $candidate->addressOfWork,
            'name_of_facility' => $candidate->nameOfFacility,
            'company_adresses_id' => $candidate->company_adresses_id,
            'dossier_number' => $candidate->dossierNumber,
            'quartal' => $candidate->quartal,
            'seasonal' => $candidate->seasonal,
            'case_id' => $candidate->case_id,
            'agent_id' => $candidate->agent_id,
            'user_id' => $candidate->user_id,
            'added_by' => $candidate->addedBy,
            'date' => $candidate->date,
            'notes' => $candidate->notes,
            'is_active' => $isActive,
            'created_at' => $candidate->created_at ?? $now,
            'updated_at' => $now,
        ]);

        return $contractId;
    }

    /**
     * Re-link satellite table records from duplicate to master profile
     */
    private function relinkSatelliteTables(int $duplicateId, int $masterId, int $contractId): void
    {
        // Re-link files
        DB::table('files')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);

        // Re-link statushistories
        DB::table('statushistories')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);

        // Re-link agent_candidates
        DB::table('agent_candidates')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);

        // Re-link invoices
        DB::table('invoices')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);

        // Re-link invoice_company_candidates (if table exists)
        if (DB::getSchemaBuilder()->hasTable('invoice_company_candidates')) {
            DB::table('invoice_company_candidates')
                ->where('candidate_id', $duplicateId)
                ->update([
                    'candidate_id' => $masterId,
                    'contract_id' => $contractId,
                ]);
        }

        // Re-link arrivals
        DB::table('arrivals')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);

        // Re-link candidate_visas
        DB::table('candidate_visas')
            ->where('candidate_id', $duplicateId)
            ->update([
                'candidate_id' => $masterId,
                'contract_id' => $contractId,
            ]);
    }

    /**
     * Add contract_id to satellite records for the master profile
     */
    private function addContractIdToMasterRecords(int $masterId, int $contractId): void
    {
        // Update files for master
        DB::table('files')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);

        // Update statushistories for master
        DB::table('statushistories')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);

        // Update agent_candidates for master
        DB::table('agent_candidates')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);

        // Update invoices for master
        DB::table('invoices')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);

        // Update invoice_company_candidates for master (if table exists)
        if (DB::getSchemaBuilder()->hasTable('invoice_company_candidates')) {
            DB::table('invoice_company_candidates')
                ->where('candidate_id', $masterId)
                ->whereNull('contract_id')
                ->update(['contract_id' => $contractId]);
        }

        // Update arrivals for master
        DB::table('arrivals')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);

        // Update candidate_visas for master
        DB::table('candidate_visas')
            ->where('candidate_id', $masterId)
            ->whereNull('contract_id')
            ->update(['contract_id' => $contractId]);
    }

    /**
     * DUAL WRITE: Sync active contract data to master profile's legacy columns
     */
    private function syncActiveContractToLegacyColumns(int $masterId, int $activeContractId): void
    {
        $activeContract = DB::table('candidate_contracts')
            ->where('id', $activeContractId)
            ->first();

        if ($activeContract) {
            DB::table('candidates')
                ->where('id', $masterId)
                ->update([
                    'company_id' => $activeContract->company_id,
                    'position_id' => $activeContract->position_id,
                    'status_id' => $activeContract->status_id,
                    'type_id' => $activeContract->type_id,
                    'contractType' => $activeContract->contract_type,
                    'contractPeriod' => $activeContract->contract_period,
                    'contractPeriodNumber' => $activeContract->contract_period_number,
                    'contractExtensionPeriod' => $activeContract->contract_extension_period,
                    'startContractDate' => $activeContract->start_contract_date,
                    'endContractDate' => $activeContract->end_contract_date,
                    'contractPeriodDate' => $activeContract->contract_period_date,
                    'salary' => $activeContract->salary,
                    'workingTime' => $activeContract->working_time,
                    'workingDays' => $activeContract->working_days,
                    'addressOfWork' => $activeContract->address_of_work,
                    'nameOfFacility' => $activeContract->name_of_facility,
                    'company_adresses_id' => $activeContract->company_adresses_id,
                    'dossierNumber' => $activeContract->dossier_number,
                    'quartal' => $activeContract->quartal,
                    'seasonal' => $activeContract->seasonal,
                    'case_id' => $activeContract->case_id,
                    'agent_id' => $activeContract->agent_id,
                    'user_id' => $activeContract->user_id,
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This rollback is destructive and should only be used in development.
     * It cannot perfectly restore the original state as some data relationships
     * have been consolidated.
     */
    public function down(): void
    {
        // Restore soft-deleted candidates (but they won't have their original satellite data)
        DB::table('candidates')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        // Remove all contract records
        DB::table('candidate_contracts')->truncate();

        // Clear contract_id from satellite tables
        DB::table('files')->update(['contract_id' => null]);
        DB::table('statushistories')->update(['contract_id' => null]);
        DB::table('agent_candidates')->update(['contract_id' => null]);
        DB::table('invoices')->update(['contract_id' => null]);
        DB::table('arrivals')->update(['contract_id' => null]);
        DB::table('candidate_visas')->update(['contract_id' => null]);

        if (DB::getSchemaBuilder()->hasTable('invoice_company_candidates')) {
            DB::table('invoice_company_candidates')->update(['contract_id' => null]);
        }

        Log::warning('Migration rolled back - some data relationships may be lost');
    }
};
