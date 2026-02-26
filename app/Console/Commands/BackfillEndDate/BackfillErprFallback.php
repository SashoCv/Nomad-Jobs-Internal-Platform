<?php

namespace App\Console\Commands\BackfillEndDate;

use App\Models\CandidateContract;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Case 5: ERPR contracts without contract_period_date.
 * Calculate from candidate.date (or start_contract_date) + years based on contract type.
 * erpr1 = +1 year, erpr2 = +2 years, erpr3 = +3 years.
 * ~2,002 records.
 */
class BackfillErprFallback extends Command
{
    protected $signature = 'backfill:end-date-erpr-fallback {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Backfill end_contract_date for ERPR contracts by calculating from candidate date + years';

    private const YEARS_MAP = [
        'erpr1' => 1,
        'erpr2' => 2,
        'erpr3' => 3,
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $contracts = CandidateContract::whereIn('contract_type', ['erpr1', 'erpr2', 'erpr3'])
            ->whereNull('end_contract_date')
            ->whereNull('contract_period_date')
            ->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$contracts->count()} contracts to update.");

        $updated = 0;
        $skipped = 0;

        foreach ($contracts as $contract) {
            $years = self::YEARS_MAP[$contract->contract_type] ?? null;
            if (!$years) {
                $this->warn("  Skipping contract #{$contract->id} — unknown type: {$contract->contract_type}");
                $skipped++;
                continue;
            }

            // Priority: start_contract_date > candidate.date
            $baseDate = null;
            $source = '';

            if ($contract->start_contract_date) {
                $baseDate = Carbon::parse($contract->start_contract_date);
                $source = 'start_contract_date';
            } else {
                $candidate = Candidate::find($contract->candidate_id);
                if ($candidate && $candidate->date) {
                    $baseDate = Carbon::parse($candidate->date);
                    $source = 'candidate.date';
                }
            }

            if (!$baseDate) {
                $this->warn("  Skipping contract #{$contract->id} (candidate #{$contract->candidate_id}) — no date to calculate from");
                $skipped++;
                continue;
            }

            $endDate = $baseDate->copy()->addYears($years)->format('Y-m-d');

            if ($dryRun) {
                $this->line("  Contract #{$contract->id} (candidate #{$contract->candidate_id}, {$contract->contract_type}): {$source} ({$baseDate->format('Y-m-d')}) + {$years}y = {$endDate}");
            } else {
                $contract->end_contract_date = $endDate;
                $contract->save();
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated: {$updated}, Skipped: {$skipped}");
    }
}
