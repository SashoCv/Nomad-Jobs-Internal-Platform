<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 2: 90-day contracts where contract_period is a date range (dd.MM.yyyy - dd.MM.yyyy).
 * Parse start from first date, end from second date. Backfill both start_contract_date and end_contract_date.
 * ~790 records.
 */
class Backfill90DaysDateRange extends Command
{
    protected $signature = 'backfill:end-date-90days-range {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill start/end dates for 90-day contracts where contract_period is a date range';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::where('contract_type', '90days')
            ->whereNull('end_contract_date')
            ->whereNotNull('contract_period')
            ->whereRaw("contract_period LIKE '%.%.% - %.%.%'")
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $parts = explode(' - ', $contract->contract_period);

            if (count($parts) !== 2) {
                $this->warn("  Skipping contract #{$contract->id} — cannot split: {$contract->contract_period}");
                $skipped++;
                continue;
            }

            $startDate = $this->parseDate(trim($parts[0]));
            $endDate = $this->parseDate(trim($parts[1]));

            if (!$startDate || !$endDate) {
                $this->warn("  Skipping contract #{$contract->id} — invalid dates: {$contract->contract_period}");
                $skipped++;
                continue;
            }

            $startFormatted = $startDate->format('Y-m-d');
            $endFormatted = $endDate->format('Y-m-d');

            if ($dryRun) {
                $startStatus = $contract->start_contract_date ? 'already set' : $startFormatted;
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): start={$startStatus}, end={$endFormatted}");
            } else {
                // Only set start_contract_date if it's not already set
                if (!$contract->start_contract_date) {
                    $contract->start_contract_date = $startFormatted;
                }
                $contract->end_contract_date = $endFormatted;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }

    private function parseDate(string $dateStr): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d.m.Y', $dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }
}
