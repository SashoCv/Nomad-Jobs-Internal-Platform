<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use Illuminate\Console\Command;

/**
 * Case 1: 90-day contracts where contract_period is an ISO date (YYYY-MM-DD).
 * This IS the end date — use it directly.
 * ~1,634 records.
 */
class Backfill90DaysIsoDate extends Command
{
    protected $signature = 'backfill:end-date-90days-iso {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for 90-day contracts where contract_period is an ISO date';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::where('contract_type', '90days')
            ->whereNull('end_contract_date')
            ->whereNotNull('contract_period')
            ->whereRaw("contract_period REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'")
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $endDate = $contract->contract_period;

            // Validate it's a real date
            $parsed = date_create($endDate);
            if (!$parsed) {
                $this->warn("  Skipping contract #{$contract->id} — invalid date: {$endDate}");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): end_contract_date = {$endDate}");
            } else {
                $contract->end_contract_date = $endDate;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }
}
