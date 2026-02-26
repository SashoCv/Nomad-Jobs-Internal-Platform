<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use Illuminate\Console\Command;

/**
 * Case 4: ERPR contracts that have contract_period_date (already correctly calculated).
 * Use contract_period_date directly as end_contract_date.
 * ~2,001 records.
 */
class BackfillErprFromPeriodDate extends Command
{
    protected $signature = 'backfill:end-date-erpr-period-date {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for ERPR contracts using existing contract_period_date';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::whereIn('contract_type', ['erpr1', 'erpr2', 'erpr3'])
            ->whereNull('end_contract_date')
            ->whereNotNull('contract_period_date')
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $endDate = $contract->contract_period_date;

            // Sanity check — contract_period_date for ERPR should be a reasonable year
            $year = (int) date('Y', strtotime($endDate));
            if ($year < 2020 || $year > 2040) {
                $this->warn("  Skipping contract #{$contract->id} — suspicious date: {$endDate}");
                $skipped++;
                continue;
            }

            $formatted = date('Y-m-d', strtotime($endDate));

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}, {$contract->contract_type}): end_contract_date = {$formatted}");
            } else {
                $contract->end_contract_date = $formatted;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }
}
