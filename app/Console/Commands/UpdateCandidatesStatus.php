<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Statushistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCandidatesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update candidates status_id with the latest status from statushistories based on statuses.order';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to update candidates status_id...');

        // Get all candidates
        $candidates = Candidate::all();
        $updatedCount = 0;
        $errorCount = 0;

        $this->info("Found {$candidates->count()} candidates to process.");

        foreach ($candidates as $candidate) {
            try {
                // Get the latest status for this candidate based on highest order
                $latestStatus = Statushistory::where('candidate_id', $candidate->id)
                    ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
                    ->select('statushistories.*', 'statuses.order')
                    ->orderBy('statuses.order', 'desc')
                    ->first();

                if ($latestStatus) {
                    // Update candidate's status_id if different
                    if ($candidate->status_id !== $latestStatus->status_id) {
                        $oldStatusId = $candidate->status_id;
                        $candidate->status_id = $latestStatus->status_id;
                        $candidate->save();

                        $this->line("Candidate {$candidate->id} ({$candidate->fullName}): {$oldStatusId} → {$latestStatus->status_id}");
                        $updatedCount++;
                    }
                } else {
                    $this->warn("No status history found for candidate {$candidate->id} ({$candidate->fullName})");
                }
            } catch (\Exception $e) {
                $this->error("Error processing candidate {$candidate->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("\nUpdate completed!");
        $this->info("Updated: {$updatedCount} candidates");
        $this->info("Errors: {$errorCount} candidates");
        $this->info("Total processed: {$candidates->count()} candidates");

        return Command::SUCCESS;
    }
}
