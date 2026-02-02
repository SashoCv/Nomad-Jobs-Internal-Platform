<?php

namespace App\Console\Commands;

use App\Models\AgentCandidate;
use App\Models\CandidateContract;
use App\Models\Category;
use App\Models\File;
use App\Models\Role;
use Illuminate\Console\Command;

class AddContractIdToAgentFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:add-contract-id
                            {--dry-run : Show what would be updated without making changes}
                            {--debug : Show detailed info about skipped files}
                            {--fix-permissions : Add Agent and Nomad staff role permissions to categories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add contract_id to files in "files from agent" category and fix category permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $debug = $this->option('debug');
        $fixPermissions = $this->option('fix-permissions');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made.');
        }

        // Get all "files from agent" categories
        $categories = Category::where('nameOfCategory', 'files from agent')->get();

        if ($fixPermissions) {
            $this->fixCategoryPermissions($categories, $dryRun);
        }

        $categoryIds = $categories->pluck('id', 'candidate_id');

        if ($categoryIds->isEmpty()) {
            $this->warn('No "files from agent" categories found.');
            return 0;
        }

        $this->info('Found ' . $categoryIds->count() . ' "files from agent" categories.');

        // Get files without contract_id in these categories
        $filesWithoutContract = File::whereIn('category_id', $categoryIds->values())
            ->whereNull('contract_id')
            ->get();

        if ($filesWithoutContract->isEmpty()) {
            $this->info('All files already have contract_id assigned.');
            return 0;
        }

        $this->info('Found ' . $filesWithoutContract->count() . ' files without contract_id.');

        $updated = 0;
        $skipped = 0;
        $skippedDetails = [];

        $this->output->progressStart($filesWithoutContract->count());

        foreach ($filesWithoutContract as $file) {
            $contractId = null;

            // Try to find contract_id from AgentCandidate record
            $agentCandidate = AgentCandidate::where('candidate_id', $file->candidate_id)->first();

            if ($agentCandidate && $agentCandidate->contract_id) {
                $contractId = $agentCandidate->contract_id;
            }

            // Fallback: try to get active contract from candidate
            if (!$contractId) {
                $candidate = $file->candidates;
                if ($candidate) {
                    $activeContract = $candidate->contracts()->where('is_active', true)->first();
                    if ($activeContract) {
                        $contractId = $activeContract->id;
                    }
                }
            }

            // Fallback 2: try to get ANY contract for the candidate
            if (!$contractId && $file->candidate_id) {
                $anyContract = CandidateContract::where('candidate_id', $file->candidate_id)->first();
                if ($anyContract) {
                    $contractId = $anyContract->id;
                }
            }

            if ($contractId) {
                if (!$dryRun) {
                    $file->contract_id = $contractId;
                    $file->save();
                }
                $updated++;
            } else {
                $skipped++;
                if ($debug) {
                    $hasAgentCandidate = $agentCandidate ? 'Yes' : 'No';
                    $agentCandidateContractId = $agentCandidate?->contract_id ?? 'null';
                    $hasCandidate = $file->candidates ? 'Yes' : 'No';
                    $contractCount = $file->candidate_id
                        ? CandidateContract::where('candidate_id', $file->candidate_id)->count()
                        : 0;

                    $skippedDetails[] = [
                        $file->id,
                        $file->candidate_id ?? 'null',
                        $file->fileName,
                        $hasAgentCandidate,
                        $agentCandidateContractId,
                        $hasCandidate,
                        $contractCount,
                    ];
                }
            }

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

        if ($debug && count($skippedDetails) > 0) {
            $this->newLine();
            $this->warn('Skipped files details:');
            $this->table(
                ['File ID', 'Candidate ID', 'File Name', 'Has AgentCandidate', 'AC Contract ID', 'Has Candidate', 'Contract Count'],
                $skippedDetails
            );
        }

        if ($dryRun && $updated > 0) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    /**
     * Fix category permissions - add Agent and Nomad staff roles
     */
    protected function fixCategoryPermissions($categories, bool $dryRun): void
    {
        $this->newLine();
        $this->info('Fixing category permissions...');

        // Roles that should have access to "files from agent" categories
        $requiredRoles = [
            Role::AGENT,
            Role::GENERAL_MANAGER,
            Role::MANAGER,
            Role::OFFICE,
            Role::HR,
            Role::OFFICE_MANAGER,
            Role::RECRUITERS,
            Role::FINANCE,
        ];

        $updated = 0;
        $alreadyCorrect = 0;

        $this->output->progressStart($categories->count());

        foreach ($categories as $category) {
            $existingRoles = $category->visibleToRoles()->pluck('roles.id')->toArray();
            $missingRoles = array_diff($requiredRoles, $existingRoles);

            if (empty($missingRoles)) {
                $alreadyCorrect++;
            } else {
                if (!$dryRun) {
                    $category->visibleToRoles()->syncWithoutDetaching($requiredRoles);
                }
                $updated++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info('Category permissions summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Updated' . ($dryRun ? ' (would be)' : ''), $updated],
                ['Already correct', $alreadyCorrect],
            ]
        );
    }
}
