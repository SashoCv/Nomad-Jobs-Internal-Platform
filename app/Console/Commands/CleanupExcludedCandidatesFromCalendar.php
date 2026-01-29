<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\Status;
use Illuminate\Console\Command;

class CleanupExcludedCandidatesFromCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:cleanup-excluded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove calendar events for candidates with terminated/refused statuses';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Cleaning up calendar events for excluded candidates...');

        $excludedStatuses = [
            Status::TERMINATED_CONTRACT,
            Status::REFUSED_MIGRATION,
            Status::REFUSED_CANDIDATE,
            Status::REFUSED_EMPLOYER,
            Status::REFUSED_BY_MIGRATION_OFFICE,
        ];

        // Get candidate IDs with excluded statuses
        $excludedCandidateIds = Candidate::whereIn('status_id', $excludedStatuses)->pluck('id');

        $this->line("Found {$excludedCandidateIds->count()} candidates with excluded statuses.");

        // Delete their calendar events
        $deleted = CalendarEvent::whereIn('candidate_id', $excludedCandidateIds)->delete();

        $this->info("Deleted {$deleted} calendar events.");

        return Command::SUCCESS;
    }
}
