<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 3: 90-day contracts with text/junk/empty contract_period.
 * Values like "90 дни", "0", NULL, or garbage data.
 * Fallback: candidate.date + 90 days (or start_contract_date + 90 if available).
 * ~330 records.
 */
class Backfill90DaysFallback extends Command
{
    protected $signature = 'backfill:end-date-90days-fallback {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for remaining 90-day contracts using candidate date + 90 days';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Get all 90-day contracts that still don't have end_contract_date
        // (should be the ones not handled by iso or range commands)
        $contracts = CandidateContract::where('contract_type', '90days')
            ->whereNull('end_contract_date')
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            // Priority: start_contract_date > candidate.date
            $baseDate = null;

            if ($contract->start_contract_date) {
                $baseDate = Carbon::parse($contract->start_contract_date);
            } else {
                $candidate = Candidate::find($contract->candidate_id);
                if ($candidate && $candidate->date) {
                    $baseDate = Carbon::parse($candidate->date);
                }
            }

            if (!$baseDate) {
                $this->warn("  Skipping contract #{$contract->id} (candidate #{$contract->candidate_id}) — no date to calculate from");
                $skipped++;
                continue;
            }

            $endDate = $baseDate->copy()->addDays(90)->format('Y-m-d');
            $source = $contract->start_contract_date ? 'start_contract_date' : 'candidate.date';

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): {$source} ({$baseDate->format('Y-m-d')}) + 90 days = {$endDate} | contract_period: " . ($contract->contract_period ?: 'NULL'));
            } else {
                $contract->end_contract_date = $endDate;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }
}
