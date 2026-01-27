<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\Status;
use App\Models\Statushistory;
use Illuminate\Console\Command;

class SyncStatusHistoriesToCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-status-histories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync candidates with specific statuses to calendar events';

    /**
     * Status ID to calendar event type mapping
     */
    protected array $statusMapping = [
        Status::RECEIVED_VISA => [
            'type' => CalendarEvent::TYPE_RECEIVED_VISA,
            'title' => 'Получил виза',
        ],
        Status::PROCEDURE_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_PROCEDURE,
            'title' => 'Процедура за ЕРПР',
        ],
        Status::LETTER_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_LETTER,
            'title' => 'Писмо за ЕРПР',
        ],
        Status::PHOTO_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_PHOTO,
            'title' => 'Снимка за ЕРПР',
        ],
        Status::HIRED => [
            'type' => CalendarEvent::TYPE_HIRED,
            'title' => 'Назначен на работа',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Syncing candidate statuses to calendar events...');

        $statusIds = array_keys($this->statusMapping);
        $counts = [];

        foreach ($statusIds as $statusId) {
            $counts[$statusId] = 0;
        }

        // Get candidates whose CURRENT status_id is one of the tracked statuses
        $candidates = Candidate::whereIn('status_id', $statusIds)
            ->with('company')
            ->get();

        foreach ($candidates as $candidate) {
            $mapping = $this->statusMapping[$candidate->status_id];

            // Get the latest status history entry for this candidate with this status
            $statusHistory = Statushistory::where('candidate_id', $candidate->id)
                ->where('status_id', $candidate->status_id)
                ->whereNotNull('statusDate')
                ->orderBy('id', 'desc')
                ->first();

            if (!$statusHistory || !$statusHistory->statusDate) {
                continue;
            }

            CalendarEvent::updateOrCreate(
                [
                    'type' => $mapping['type'],
                    'candidate_id' => $candidate->id,
                ],
                [
                    'title' => $mapping['title'],
                    'date' => $statusHistory->statusDate,
                    'company_id' => $candidate->company_id,
                    'description' => $statusHistory->description,
                ]
            );

            $counts[$candidate->status_id]++;
        }

        $this->info('Sync completed:');
        foreach ($this->statusMapping as $statusId => $mapping) {
            $this->line("  - {$mapping['title']}: {$counts[$statusId]} events");
        }

        $total = array_sum($counts);
        $this->info("Total: {$total} status events synced to calendar.");

        return Command::SUCCESS;
    }
}
