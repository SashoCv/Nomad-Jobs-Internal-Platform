<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\CandidateVisa;
use Illuminate\Console\Command;

class SyncVisaExpiryToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-visa-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all candidate visas with expiry dates to calendar events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Syncing visa expiry dates to calendar events...');

        $visas = CandidateVisa::with('candidate')
            ->whereNotNull('end_date')
            ->get();

        $count = 0;

        foreach ($visas as $visa) {
            if (!$visa->candidate) {
                continue;
            }

            CalendarEvent::updateOrCreate(
                [
                    'type' => CalendarEvent::TYPE_VISA_EXPIRY,
                    'candidate_id' => $visa->candidate_id,
                ],
                [
                    'title' => 'Изтичаща виза',
                    'date' => $visa->end_date,
                    'company_id' => $visa->candidate->company_id,
                    'description' => 'Визата изтича на ' . $visa->end_date->format('d.m.Y'),
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} visa expiry events to calendar.");

        return Command::SUCCESS;
    }
}
