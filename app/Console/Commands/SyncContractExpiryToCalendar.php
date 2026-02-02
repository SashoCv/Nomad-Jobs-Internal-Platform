<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncContractExpiryToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-contract-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all candidates with contract expiry dates to calendar events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Syncing contract expiry dates to calendar events...');

        // Exclude candidates with terminated/refused statuses
        $excludedStatuses = [
            Status::TERMINATED_CONTRACT,
            Status::REFUSED_MIGRATION,
            Status::REFUSED_CANDIDATE,
            Status::REFUSED_EMPLOYER,
            Status::REFUSED_BY_MIGRATION_OFFICE,
        ];

        $candidates = Candidate::whereNotNull('endContractDate')
            ->whereNotIn('status_id', $excludedStatuses)
            ->select('id', 'endContractDate', 'company_id')
            ->get();

        $count = 0;

        foreach ($candidates as $candidate) {
            CalendarEvent::updateOrCreate(
                [
                    'type' => CalendarEvent::TYPE_CONTRACT_EXPIRY,
                    'candidate_id' => $candidate->id,
                ],
                [
                    'title' => 'Изтичащ договор',
                    'date' => Carbon::parse($candidate->endContractDate)->format('Y-m-d'),
                    'company_id' => $candidate->company_id,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} contract expiry events to calendar.");

        return Command::SUCCESS;
    }
}
