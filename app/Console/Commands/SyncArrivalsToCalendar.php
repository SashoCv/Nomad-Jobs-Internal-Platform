<?php

namespace App\Console\Commands;

use App\Models\Arrival;
use App\Models\CalendarEvent;
use Illuminate\Console\Command;

class SyncArrivalsToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-arrivals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all arrivals to calendar events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Syncing arrivals to calendar events...');

        $arrivals = Arrival::whereNotNull('arrival_date')
            ->whereNotNull('candidate_id')
            ->get();

        $count = 0;

        foreach ($arrivals as $arrival) {
            CalendarEvent::updateOrCreate(
                [
                    'type' => CalendarEvent::TYPE_ARRIVAL,
                    'candidate_id' => $arrival->candidate_id,
                ],
                [
                    'title' => 'Пристигане',
                    'date' => $arrival->arrival_date,
                    'time' => $arrival->arrival_time,
                    'company_id' => $arrival->company_id,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} arrival events to calendar.");

        return Command::SUCCESS;
    }
}
