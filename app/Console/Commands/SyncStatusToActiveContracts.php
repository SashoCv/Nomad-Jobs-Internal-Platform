<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStatusToActiveContracts extends Command
{
    protected $signature = 'candidates:sync-status-to-contracts
                            {--dry-run : Show what would be done without making changes}
                            {--candidate_id= : Sync a specific candidate by ID}';

    protected $description = 'Push candidate status_id into active contracts (candidate is source of truth)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $candidateId = $this->option('candidate_id');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Sync Candidate status_id → Active Contracts ===');
        $this->info('');

        // Find mismatches: candidate.status_id != activeContract.status_id
        $query = DB::table('candidates')
            ->join('candidate_contracts', function ($join) {
                $join->on('candidates.id', '=', 'candidate_contracts.candidate_id')
                    ->where('candidate_contracts.is_active', true);
            })
            ->whereColumn('candidates.status_id', '!=', 'candidate_contracts.status_id')
            ->orWhere(function ($q) {
                $q->whereNotNull('candidates.status_id')
                    ->whereNull('candidate_contracts.status_id')
                    ->where('candidate_contracts.is_active', true);
            })
            ->select(
                'candidates.id',
                'candidates.fullName',
                'candidates.fullNameCyrillic',
                'candidates.status_id as candidate_status_id',
                'candidate_contracts.id as contract_id',
                'candidate_contracts.status_id as contract_status_id'
            );

        if ($candidateId) {
            $query->where('candidates.id', $candidateId);
        }

        $mismatches = $query->get();

        $this->info("Found {$mismatches->count()} mismatches");
        $this->info('');

        if ($mismatches->isEmpty()) {
            $this->info('Everything is in sync.');
            return Command::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($mismatches as $row) {
            $name = $row->fullNameCyrillic ?: $row->fullName;
            $this->line("Candidate #{$row->id} - {$name}");
            $this->line("  contract.status_id: {$this->fmt($row->contract_status_id)} -> {$this->fmt($row->candidate_status_id)}");

            if (!$dryRun) {
                try {
                    CandidateContract::where('id', $row->contract_id)
                        ->update(['status_id' => $row->candidate_status_id]);
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  Failed: {$e->getMessage()}");
                    Log::error("Failed to sync status for candidate {$row->id}: {$e->getMessage()}");
                }
            } else {
                $synced++;
            }
        }

        $this->info('');
        $this->info('=== Summary ===');
        $action = $dryRun ? 'Would sync' : 'Synced';
        $this->info("{$action}: {$synced}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return Command::SUCCESS;
    }

    private function fmt($val): string
    {
        return $val === null ? 'NULL' : (string) $val;
    }
}
