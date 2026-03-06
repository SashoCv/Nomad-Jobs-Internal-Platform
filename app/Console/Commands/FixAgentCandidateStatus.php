<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAgentCandidateStatus extends Command
{
    protected $signature = 'candidates:fix-agent-status
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Null out status_id for agent candidates that were incorrectly given status_id=1 without a status history record';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Fix Agent Candidates with Incorrect status_id ===');
        $this->info('');

        $candidates = DB::table('candidates')
            ->leftJoin('users', 'candidates.agent_id', '=', 'users.id')
            ->leftJoin('agent_candidates', function ($join) {
                $join->on('candidates.id', '=', 'agent_candidates.candidate_id')
                    ->whereNull('agent_candidates.deleted_at');
            })
            ->leftJoin('status_for_candidate_from_agents', 'agent_candidates.status_for_candidate_from_agent_id', '=', 'status_for_candidate_from_agents.id')
            ->whereNotNull('candidates.agent_id')
            ->where('candidates.status_id', 1)
            ->whereNull('candidates.deleted_at')
            ->where('candidates.created_at', '>=', '2026-01-01')
            ->whereNotIn('candidates.id', function ($q) {
                $q->select('candidate_id')->distinct()->from('statushistories');
            })
            ->select(
                'candidates.id',
                'candidates.fullName',
                'candidates.fullNameCyrillic',
                'candidates.agent_id',
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as agent_name"),
                'candidates.created_at',
                'agent_candidates.status_for_candidate_from_agent_id as agent_status_id',
                'status_for_candidate_from_agents.name as agent_status_name'
            )
            ->orderBy('candidates.created_at', 'desc')
            ->get();

        $this->info("Found {$candidates->count()} candidates to fix");
        $this->info('');

        if ($candidates->isEmpty()) {
            $this->info('Nothing to fix.');
            return Command::SUCCESS;
        }

        $fixed = 0;
        $failed = 0;

        foreach ($candidates as $row) {
            $name = $row->fullNameCyrillic ?: $row->fullName;
            $this->line("Candidate #{$row->id} - {$name}");
            $this->line("  Agent: {$row->agent_name} (ID: {$row->agent_id})");
            $this->line("  Created: {$row->created_at}");
            $this->line("  Agent approval: {$row->agent_status_name} (ID: {$row->agent_status_id})");
            $this->line("  candidates.status_id: 1 -> NULL");

            if (!$dryRun) {
                try {
                    DB::transaction(function () use ($row) {
                        DB::table('candidates')
                            ->where('id', $row->id)
                            ->update(['status_id' => null]);

                        DB::table('candidate_contracts')
                            ->where('candidate_id', $row->id)
                            ->where('is_active', true)
                            ->update(['status_id' => null]);
                    });

                    $this->line("  -> Fixed");
                    $fixed++;
                } catch (\Exception $e) {
                    $this->error("  -> Failed: {$e->getMessage()}");
                    Log::error("Failed to fix agent candidate {$row->id}: {$e->getMessage()}");
                    $failed++;
                }
            } else {
                $fixed++;
            }

            $this->line('');
        }

        $this->info('=== Summary ===');
        $action = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("{$action}: {$fixed}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return Command::SUCCESS;
    }
}
