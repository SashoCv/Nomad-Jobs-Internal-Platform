<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCandidatesFromActiveContract extends Command
{
    protected $signature = 'candidates:sync-from-contracts
                            {--dry-run : Show what would be done without making changes}
                            {--candidate_id= : Sync a specific candidate by ID}';

    protected $description = 'Sync candidate records from their active contract (company, position, salary, etc.)';

    /**
     * Fields to sync from active contract to candidate.
     * Format: 'candidate_column' => 'contract_column'
     */
    private const SYNC_FIELDS = [
        'company_id' => 'company_id',
        'position_id' => 'position_id',
        'type_id' => 'type_id',
        'contractType' => 'contract_type',
        'contract_type_id' => 'contract_type_id',
        'contractPeriod' => 'contract_period',
        'startContractDate' => 'start_contract_date',
        'endContractDate' => 'end_contract_date',
        'salary' => 'salary',
        'workingTime' => 'working_time',
        'workingDays' => 'working_days',
        'addressOfWork' => 'address_of_work',
        'nameOfFacility' => 'name_of_facility',
        'dossierNumber' => 'dossier_number',
        'company_adresses_id' => 'company_adresses_id',
        'agent_id' => 'agent_id',
        'candidate_source' => 'candidate_source',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $candidateId = $this->option('candidate_id');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Sync Candidates from Active Contracts ===');
        $this->info('');

        $query = Candidate::with(['activeContract', 'activeContract.company', 'company'])
            ->whereHas('activeContract');

        if ($candidateId) {
            $query->where('id', $candidateId);
        }

        $candidates = $query->get();
        $this->info("Found {$candidates->count()} candidates with active contracts");
        $this->info('');

        $mismatched = 0;
        $synced = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            $contract = $candidate->activeContract;
            $diffs = $this->findDiffs($candidate, $contract);

            if (empty($diffs)) {
                continue;
            }

            $mismatched++;

            $candidateName = $candidate->fullNameCyrillic ?: $candidate->fullName;
            $this->line("Candidate #{$candidate->id} - {$candidateName}");

            foreach ($diffs as $diff) {
                $this->line("  {$diff['field']}: {$diff['old']} -> {$diff['new']}");
            }

            if (!$dryRun) {
                try {
                    $updateData = [];
                    foreach (self::SYNC_FIELDS as $candidateCol => $contractCol) {
                        $updateData[$candidateCol] = $contract->{$contractCol};
                    }

                    // Merge notes instead of overwriting
                    $updateData['notes'] = $this->mergeNotes($candidate->notes, $contract->notes);

                    $candidate->update($updateData);
                    $synced++;
                    $this->info("  Synced.");
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  Failed: {$e->getMessage()}");
                    Log::error("Failed to sync candidate {$candidate->id}: {$e->getMessage()}");
                }
            } else {
                $synced++;
            }

            $this->line('');
        }

        $this->info('=== Summary ===');
        $this->info("Total candidates with active contracts: {$candidates->count()}");
        $this->info("Mismatched: {$mismatched}");

        $action = $dryRun ? 'Would sync' : 'Synced';
        $this->info("{$action}: {$synced}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return Command::SUCCESS;
    }

    /**
     * Compare candidate fields with active contract fields and return differences.
     */
    private function findDiffs(Candidate $candidate, $contract): array
    {
        $diffs = [];

        foreach (self::SYNC_FIELDS as $candidateCol => $contractCol) {
            $candidateVal = $candidate->{$candidateCol};
            $contractVal = $contract->{$contractCol};

            // Normalize for comparison (treat null and empty string as equal)
            $normalizedCandidate = $candidateVal === '' ? null : $candidateVal;
            $normalizedContract = $contractVal === '' ? null : $contractVal;

            // Skip if contract value is null (don't overwrite with nothing)
            if ($normalizedContract === null) {
                continue;
            }

            // Use loose comparison for numeric strings vs integers
            if ($normalizedCandidate != $normalizedContract) {
                $oldLabel = $this->formatValue($candidateVal);
                $newLabel = $this->formatValue($contractVal);

                // For company_id, show the company name too
                if ($candidateCol === 'company_id') {
                    $oldCompanyName = $candidate->company?->nameOfCompany;
                    $newCompanyName = $contract->company?->nameOfCompany;
                    if ($oldCompanyName) $oldLabel .= " ({$oldCompanyName})";
                    if ($newCompanyName) $newLabel .= " ({$newCompanyName})";
                }

                $diffs[] = [
                    'field' => $candidateCol,
                    'old' => $oldLabel,
                    'new' => $newLabel,
                ];
            }
        }

        // Check notes separately (merged, not overwritten)
        $candidateNotes = trim($candidate->notes ?? '');
        $contractNotes = trim($contract->notes ?? '');

        if ($contractNotes !== '' && !str_contains($candidateNotes, $contractNotes)) {
            $merged = $this->mergeNotes($candidate->notes, $contract->notes);
            $diffs[] = [
                'field' => 'notes (merge)',
                'old' => $this->formatValue($candidate->notes),
                'new' => $this->formatValue($merged),
            ];
        }

        return $diffs;
    }

    /**
     * Merge candidate and contract notes, avoiding duplicates.
     */
    private function mergeNotes(?string $candidateNotes, ?string $contractNotes): ?string
    {
        $candidate = trim($candidateNotes ?? '');
        $contract = trim($contractNotes ?? '');

        if ($candidate === '' && $contract === '') {
            return null;
        }

        if ($candidate === '') {
            return $contract;
        }

        if ($contract === '' || str_contains($candidate, $contract)) {
            return $candidate;
        }

        return $candidate . "\n" . $contract;
    }

    private function formatValue($value): string
    {
        if ($value === null) return 'NULL';
        if ($value === '') return '(empty)';
        return (string) $value;
    }
}
