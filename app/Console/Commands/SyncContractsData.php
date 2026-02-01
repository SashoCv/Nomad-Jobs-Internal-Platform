<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncContractsData extends Command
{
    protected $signature = 'contracts:sync {--dry-run : Show what would be done without making changes}';

    protected $description = 'Sync contract data: create missing contracts and fix status mismatches';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Contract Data Sync ===');
        $this->info('');

        // Part 1: Create missing contracts for candidates without any contract
        $this->createMissingContracts($dryRun);

        $this->info('');

        // Part 2: Sync status_id mismatches
        $this->syncStatusMismatches($dryRun);

        $this->info('');
        $this->info('=== Sync Complete ===');

        return Command::SUCCESS;
    }

    private function createMissingContracts(bool $dryRun): void
    {
        $this->info('Part 1: Creating missing contracts...');

        $candidatesWithoutContracts = Candidate::whereDoesntHave('contracts')->get();

        $this->info("Found {$candidatesWithoutContracts->count()} candidates without contracts");

        if ($candidatesWithoutContracts->isEmpty()) {
            $this->info('No missing contracts to create.');
            return;
        }

        $created = 0;

        foreach ($candidatesWithoutContracts as $candidate) {
            $this->line("  - ID: {$candidate->id} | Name: {$candidate->fullName}");

            if (!$dryRun) {
                try {
                    // Note: contract_type mutator automatically sets contract_type_id
                    CandidateContract::create([
                        'candidate_id' => $candidate->id,
                        'contract_period_number' => 1,
                        'is_active' => true,
                        'company_id' => $candidate->company_id,
                        'position_id' => $candidate->position_id,
                        'status_id' => $candidate->status_id,
                        'type_id' => $candidate->type_id,
                        'contract_type' => $candidate->contractType ?? 'erpr1',
                        'contract_period' => $candidate->contractPeriod,
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
                        'notes' => $candidate->notes,
                        'date' => $candidate->date ?? $candidate->created_at,
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    $this->error("    Failed to create contract: {$e->getMessage()}");
                    Log::error("Failed to create contract for candidate {$candidate->id}: {$e->getMessage()}");
                }
            } else {
                $created++;
            }
        }

        $action = $dryRun ? 'Would create' : 'Created';
        $this->info("{$action} {$created} contracts");
    }

    private function syncStatusMismatches(bool $dryRun): void
    {
        $this->info('Part 2: Syncing status mismatches...');

        $mismatches = [];

        $candidates = Candidate::with('activeContract')
            ->whereHas('activeContract')
            ->get();

        foreach ($candidates as $candidate) {
            $contract = $candidate->activeContract;

            if ($candidate->status_id != $contract->status_id) {
                $mismatches[] = [
                    'candidate' => $candidate,
                    'contract' => $contract,
                ];
            }
        }

        $this->info("Found " . count($mismatches) . " status mismatches");

        if (empty($mismatches)) {
            $this->info('No status mismatches to fix.');
            return;
        }

        $fixed = 0;

        foreach ($mismatches as $mismatch) {
            $candidate = $mismatch['candidate'];
            $contract = $mismatch['contract'];

            $this->line("  - Candidate ID: {$candidate->id} | Status: {$candidate->status_id} -> Contract status: {$contract->status_id}");

            if (!$dryRun) {
                try {
                    // Update contract's status_id to match candidate's status_id
                    $contract->status_id = $candidate->status_id;
                    $contract->save();
                    $fixed++;
                } catch (\Exception $e) {
                    $this->error("    Failed to sync: {$e->getMessage()}");
                    Log::error("Failed to sync status for candidate {$candidate->id}: {$e->getMessage()}");
                }
            } else {
                $fixed++;
            }
        }

        $action = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("{$action} {$fixed} status mismatches");
    }
}
