<?php

namespace App\Console\Commands;

use App\Models\AgentCandidate;
use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMissingAgentCandidates extends Command
{
    protected $signature = 'candidates:fix-missing-agent-candidates
                            {--dry-run : Show what would happen without making changes (default)}
                            {--execute : Actually create the missing records}';

    protected $description = 'Create missing agent_candidates records for candidates that have agent_id but no agent_candidates entry';

    public function handle(): int
    {
        $execute = $this->option('execute');

        if (! $execute) {
            $this->info('=== DRY RUN MODE (no changes will be made) ===');
            $this->info('Use --execute to create the missing records.');
            $this->info('');
        } else {
            $this->warn('=== EXECUTE MODE — records will be created ===');
            $this->info('');
        }

        // Find candidates with agent_id that have no agent_candidates record
        $candidates = Candidate::whereNotNull('agent_id')
            ->where('agent_id', '!=', 0)
            ->whereNull('deleted_at')
            ->whereDoesntHave('agentCandidates')
            ->with(['agent:id,firstName,lastName,email', 'activeContract.company', 'activeContract.position'])
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates with missing agent_candidates records found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$candidates->count()} candidate(s) with agent_id but no agent_candidates record.");
        $this->info('');

        $created = 0;

        if ($execute) {
            DB::beginTransaction();
        }

        try {
            foreach ($candidates as $candidate) {
                $agent = $candidate->agent;
                $contract = $candidate->activeContract;
                $agentName = $agent ? "{$agent->firstName} {$agent->lastName}" : "ID:{$candidate->agent_id}";
                $companyName = $contract?->company?->nameOfCompany ?? 'N/A';
                $positionName = $contract?->position?->jobPosition ?? 'N/A';

                $this->line("  #{$candidate->id} {$candidate->fullName} → Agent: {$agentName} | Company: {$companyName} | Position: {$positionName}");

                if ($execute) {
                    AgentCandidate::create([
                        'user_id' => $candidate->agent_id,
                        'candidate_id' => $candidate->id,
                        'contract_id' => $contract?->id,
                        'company_job_id' => null,
                        'status_for_candidate_from_agent_id' => 3, // Approved
                    ]);
                }

                $created++;
            }

            if ($execute) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if ($execute) {
                DB::rollBack();
            }
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->info('');
        $this->info($execute
            ? "Created {$created} agent_candidates record(s)."
            : "Would create {$created} agent_candidates record(s). Run with --execute to apply.");

        return Command::SUCCESS;
    }
}
