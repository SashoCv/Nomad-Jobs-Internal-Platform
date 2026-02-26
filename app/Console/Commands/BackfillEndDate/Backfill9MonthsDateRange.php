<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 6: 9-month contracts.
 * Some have date ranges in contract_period, others have text like "6 месеца" / "9 месеца".
 * For date ranges: parse end date.
 * For text/empty: candidate.date + 9 months.
 * 50 records total.
 */
class Backfill9MonthsDateRange extends Command
{
    protected $signature = 'backfill:end-date-9months {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for 9-month contracts';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::where('contract_type', '9months')
            ->whereNull('end_contract_date')
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $period = $contract->contract_period;
            $endDate = null;
            $method = '';

            // Try to parse as date range (dd.MM.yyyy - dd.MM.yyyy)
            if ($period && preg_match('/(\d{2}\.\d{2}\.\d{4})\s*-\s*(\d{2}\.\d{2}\.\d{4})/', $period, $matches)) {
                try {
                    $endDate = Carbon::createFromFormat('d.m.Y', $matches[2])->format('Y-m-d');
                    $method = "parsed from range";

                    // Also backfill start if missing
                    if (!$contract->start_contract_date) {
                        $startDate = Carbon::createFromFormat('d.m.Y', $matches[1])->format('Y-m-d');
                        if (!$dryRun) {
                            $contract->start_contract_date = $startDate;
                        }
                        $method .= " (start={$startDate})";
                    }
                } catch (\Exception $e) {
                    // Fall through to fallback
                }
            }

            // Try ISO date
            if (!$endDate && $period && preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
                $endDate = $period;
                $method = "ISO date from contract_period";
            }

            // Fallback: candidate.date + 9 months
            if (!$endDate) {
                $baseDate = null;

                if ($contract->start_contract_date) {
                    $baseDate = Carbon::parse($contract->start_contract_date);
                    $method = "start_contract_date + 9 months";
                } else {
                    $candidate = Candidate::find($contract->candidate_id);
                    if ($candidate && $candidate->date) {
                        $baseDate = Carbon::parse($candidate->date);
                        $method = "candidate.date ({$candidate->date}) + 9 months";
                    }
                }

                if ($baseDate) {
                    $endDate = $baseDate->copy()->addMonths(9)->format('Y-m-d');
                }
            }

            if (!$endDate) {
                $this->warn("  Skipping contract #{$contract->id} (candidate #{$contract->candidate_id}) — no data to calculate from");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}): end_contract_date = {$endDate} ({$method}) | contract_period: " . ($period ?: 'NULL'));
            } else {
                $contract->end_contract_date = $endDate;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }
}
