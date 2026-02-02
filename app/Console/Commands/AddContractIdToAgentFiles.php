<?php

namespace App\Console\Commands;

use App\Models\AgentCandidate;
use App\Models\Category;
use App\Models\File;
use Illuminate\Console\Command;

class AddContractIdToAgentFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:add-contract-id {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add contract_id to files in "files from agent" category that are missing it';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made.');
        }

        // Get all "files from agent" categories
        $categories = Category::where('nameOfCategory', 'files from agent')->pluck('id', 'candidate_id');

        if ($categories->isEmpty()) {
            $this->warn('No "files from agent" categories found.');
            return 0;
        }

        $this->info('Found ' . $categories->count() . ' "files from agent" categories.');

        // Get files without contract_id in these categories
        $filesWithoutContract = File::whereIn('category_id', $categories->values())
            ->whereNull('contract_id')
            ->get();

        if ($filesWithoutContract->isEmpty()) {
            $this->info('All files already have contract_id assigned.');
            return 0;
        }

        $this->info('Found ' . $filesWithoutContract->count() . ' files without contract_id.');

        $updated = 0;
        $skipped = 0;

        $this->output->progressStart($filesWithoutContract->count());

        foreach ($filesWithoutContract as $file) {
            // Try to find contract_id from AgentCandidate record
            $agentCandidate = AgentCandidate::where('candidate_id', $file->candidate_id)->first();

            if ($agentCandidate && $agentCandidate->contract_id) {
                if (!$dryRun) {
                    $file->contract_id = $agentCandidate->contract_id;
                    $file->save();
                }
                $updated++;
                $this->output->progressAdvance();
                continue;
            }

            // Fallback: try to get active contract from candidate
            $candidate = $file->candidates;
            if ($candidate) {
                $activeContract = $candidate->contracts()->where('is_active', true)->first();
                if ($activeContract) {
                    if (!$dryRun) {
                        $file->contract_id = $activeContract->id;
                        $file->save();
                    }
                    $updated++;
                    $this->output->progressAdvance();
                    continue;
                }
            }

            // No contract found
            $skipped++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Updated' . ($dryRun ? ' (would be)' : ''), $updated],
                ['Skipped (no contract found)', $skipped],
            ]
        );

        if ($dryRun && $updated > 0) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply changes.');
        }

        return 0;
    }
}
