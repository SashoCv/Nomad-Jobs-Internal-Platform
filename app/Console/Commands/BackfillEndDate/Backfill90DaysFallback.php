<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 3: 90-day contracts with text/junk contract_period (non-NULL).
 * Values like "90 дни", malformed date ranges, or garbage data.
 * Only processes contracts that HAVE contract_period set — skips new candidates with NULL (no contract yet).
 * Fallback: candidate.date + 90 days (or start_contract_date + 90 if available).
 * ~123 records.
 */
class Backfill90DaysFallback extends Command
{
    protected $signature = 'backfill:end-date-90days-fallback {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for remaining 90-day contracts using candidate date + 90 days';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Only process contracts that have contract_period set (old data with text/junk values).
        // Contracts with NULL contract_period are new candidates without a contract yet — leave them alone.
        $contracts = CandidateContract::where('contract_type', '90days')
            ->whereNull('end_contract_date')
            ->whereNotNull('contract_period')
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
