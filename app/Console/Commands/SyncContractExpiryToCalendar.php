<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Candidate;
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

        $candidates = Candidate::whereNotNull('endContractDate')
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
                    'date' => $candidate->endContractDate,
                    'company_id' => $candidate->company_id,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} contract expiry events to calendar.");

        return Command::SUCCESS;
    }
}
