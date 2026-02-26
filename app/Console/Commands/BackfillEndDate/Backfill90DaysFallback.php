<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 3: 90-day contracts with malformed date ranges in contract_period.
 * Handles formats missed by command #2: "15.12.2023-14.03.2024", "10.12.2023 09.03.2024", etc.
 * Only extracts dates from contract_period values that contain actual date ranges.
 * Skips text labels ("90 дни") and junk values — we can't reliably calculate dates for those.
 * ~21 records.
 */
class Backfill90DaysFallback extends Command
{
    protected $signature = 'backfill:end-date-90days-fallback {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill start/end dates for 90-day contracts with malformed date ranges in contract_period';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::where('contract_type', '90days')
            ->whereNull('end_contract_date')
            ->whereNotNull('contract_period')
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to check.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $period = $contract->contract_period;

            // Try to parse malformed date ranges:
            // "15.12.2023-14.03.2024" (no spaces around dash)
            // "10.12.2023 09.03.2024" (space separator)
            // "27,04,204-26,07,2024" (commas instead of dots)
            $startDate = null;
            $endDate = null;

            // Normalize commas to dots
            $normalized = str_replace(',', '.', $period);

            // Match two dates separated by dash or space: dd.mm.yyyy[-/ ]dd.mm.yyyy
            if (preg_match('/(\d{2}\.\d{2}\.\d{3,4})\s*[-\s]\s*(\d{2}\.\d{2}\.\d{4})/', $normalized, $matches)) {
                try {
                    $startDate = Carbon::createFromFormat('d.m.Y', $matches[1]);
                    $endDate = Carbon::createFromFormat('d.m.Y', $matches[2]);
                } catch (\Exception $e) {
                    // Could not parse — skip
                }
            }

            if (!$endDate) {
                $this->line("  Skipping contract #{$contract->id} (candidate #{$contract->candidate_id}) — not a date range: \"{$period}\"");
                $skipped++;
                continue;
            }

            $startFormatted = $startDate->format('Y-m-d');
            $endFormatted = $endDate->format('Y-m-d');

            if ($dryRun) {
                $startStatus = $contract->start_contract_date ? 'already set' : $startFormatted;
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): start={$startStatus}, end={$endFormatted} | parsed from: \"{$period}\"");
            } else {
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
}
