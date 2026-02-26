<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\Candidate;
use App\Models\CandidateContract;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 1: 90-day contracts where contract_period is an ISO date (YYYY-MM-DD).
 * contract_period = startContractDate + 90 days, so it IS the end date.
 * Also backfills start_contract_date from candidates.startContractDate or reverse-calculated (end - 90 days).
 * ~1,635 records.
 */
class Backfill90DaysIsoDate extends Command
{
    protected $signature = 'backfill:end-date-90days-iso {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date and start_contract_date for 90-day contracts where contract_period is an ISO date';

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
        $startBackfilled = 0;

        foreach ($contracts as $contract) {
            $endDate = $contract->contract_period;

            // Validate it's a real date
            $parsed = date_create($endDate);
            if (!$parsed) {
                $this->warn("  Skipping contract #{$contract->id} — invalid date: {$endDate}");
                $skipped++;
                continue;
            }

            // Backfill start_contract_date if missing
            $startInfo = '';
            if (!$contract->start_contract_date) {
                $startDate = null;
                $source = '';

                // Priority 1: Copy from candidates.startContractDate
                $candidate = Candidate::find($contract->candidate_id);
                if ($candidate && $candidate->startContractDate) {
                    $startDate = Carbon::parse($candidate->startContractDate)->format('Y-m-d');
                    $source = 'candidates.startContractDate';
                } else {
                    // Priority 2: Reverse-calculate end_date - 90 days
                    $startDate = Carbon::parse($endDate)->subDays(90)->format('Y-m-d');
                    $source = 'end_date - 90 days';
                }

                $startInfo = ", start_contract_date = {$startDate} ({$source})";

                if (!$dryRun) {
                    $contract->start_contract_date = $startDate;
                }

                $startBackfilled++;
            }

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): end_contract_date = {$endDate}{$startInfo}");
            } else {
                $contract->end_contract_date = $endDate;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}, Start dates backfilled: {$startBackfilled}");
    }
}
