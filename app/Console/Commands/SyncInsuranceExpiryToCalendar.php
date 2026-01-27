<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\MedicalInsurance;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncInsuranceExpiryToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-insurance-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all medical insurance expiry dates to calendar events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Syncing insurance expiry dates to calendar events...');

        // Exclude candidates with terminated/refused statuses
        $excludedStatuses = [
            Status::TERMINATED_CONTRACT,
            Status::REFUSED_MIGRATION,
            Status::REFUSED_CANDIDATE,
            Status::REFUSED_EMPLOYER,
            Status::REFUSED_BY_MIGRATION_OFFICE,
        ];

        $insurances = MedicalInsurance::with('candidate:id,company_id,status_id')
            ->whereHas('candidate', function ($query) use ($excludedStatuses) {
                $query->whereNotIn('status_id', $excludedStatuses);
            })
            ->whereNotNull('dateTo')
            ->where('dateTo', '!=', '')
            ->get();

        $count = 0;
        $skipped = 0;

        foreach ($insurances as $insurance) {
            try {
                // Parse the date - handle different formats
                $dateTo = $this->parseDate($insurance->dateTo);

                if (!$dateTo) {
                    $skipped++;
                    continue;
                }

                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_INSURANCE_EXPIRY,
                        'candidate_id' => $insurance->candidate_id,
                    ],
                    [
                        'title' => 'Изтичаща застраховка',
                        'date' => $dateTo->format('Y-m-d'),
                        'company_id' => $insurance->candidate?->company_id,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $this->warn("Skipped insurance ID {$insurance->id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Synced {$count} insurance expiry events to calendar.");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} records due to invalid dates.");
        }

        return Command::SUCCESS;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        // Try different formats
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try Carbon's generic parsing as fallback
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
