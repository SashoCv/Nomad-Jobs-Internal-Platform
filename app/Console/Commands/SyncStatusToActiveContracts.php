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

    protected $description = 'Push candidate status_id and type_id into active contracts (candidate is source of truth)';

    /**
     * Fields where the candidate is the source of truth (candidate → contract).
     */
    private const CANDIDATE_OWNED_FIELDS = ['status_id', 'type_id'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $candidateId = $this->option('candidate_id');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Sync Candidate → Active Contracts (status_id, type_id) ===');
        $this->info('');

        $query = DB::table('candidates')
            ->join('candidate_contracts', function ($join) {
                $join->on('candidates.id', '=', 'candidate_contracts.candidate_id')
                    ->where('candidate_contracts.is_active', true);
            })
            ->where(function ($q) {
                foreach (self::CANDIDATE_OWNED_FIELDS as $field) {
                    $q->orWhere(function ($sub) use ($field) {
                        $sub->whereColumn("candidates.{$field}", '!=', "candidate_contracts.{$field}");
                    })->orWhere(function ($sub) use ($field) {
                        $sub->whereNotNull("candidates.{$field}")
                            ->whereNull("candidate_contracts.{$field}");
                    });
                }
            })
            ->select(
                'candidates.id',
                'candidates.fullName',
                'candidates.fullNameCyrillic',
                'candidates.status_id as candidate_status_id',
                'candidates.type_id as candidate_type_id',
                'candidate_contracts.id as contract_id',
                'candidate_contracts.status_id as contract_status_id',
                'candidate_contracts.type_id as contract_type_id'
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

            $updateData = [];

            if ($row->candidate_status_id != $row->contract_status_id) {
                $this->line("  contract.status_id: {$this->fmt($row->contract_status_id)} -> {$this->fmt($row->candidate_status_id)}");
                $updateData['status_id'] = $row->candidate_status_id;
            }

            if ($row->candidate_type_id != $row->contract_type_id) {
                $this->line("  contract.type_id: {$this->fmt($row->contract_type_id)} -> {$this->fmt($row->candidate_type_id)}");
                $updateData['type_id'] = $row->candidate_type_id;
            }

            if (!$dryRun) {
                try {
                    CandidateContract::where('id', $row->contract_id)
                        ->update($updateData);
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  Failed: {$e->getMessage()}");
                    Log::error("Failed to sync candidate {$row->id}: {$e->getMessage()}");
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
