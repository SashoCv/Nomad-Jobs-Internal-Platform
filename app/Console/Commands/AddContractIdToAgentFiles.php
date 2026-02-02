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
                            {--fix-permissions : Add Agent and Nomad staff role permissions to categories}
                            {--fix-categories : Create missing categories and fix files with wrong category_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix agent files: create missing categories, fix wrong category_id, add permissions, and set contract_id';

    /**
     * Roles that should have access to "files from agent" categories
     */
    protected array $requiredRoles = [
        Role::AGENT,
        Role::GENERAL_MANAGER,
        Role::MANAGER,
        Role::OFFICE,
        Role::HR,
        Role::OFFICE_MANAGER,
        Role::RECRUITERS,
        Role::FINANCE,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $debug = $this->option('debug');
        $fixPermissions = $this->option('fix-permissions');
        $fixCategories = $this->option('fix-categories');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made.');
        }

        // Step 1: Fix categories (create missing, fix wrong category_id on files)
        if ($fixCategories) {
            $this->fixMissingCategories($dryRun, $debug);
            $this->fixFilesWithWrongCategoryId($dryRun, $debug);
        }

        // Step 2: Get all "files from agent" categories
        $categories = Category::where('nameOfCategory', 'files from agent')->get();

        // Step 3: Fix permissions
        if ($fixPermissions) {
            $this->fixCategoryPermissions($categories, $dryRun);
        }

        // Step 4: Fix contract_id on files
        $this->fixContractIds($categories, $dryRun, $debug);

        return 0;
    }

    /**
     * Create missing "files from agent" categories for AgentCandidates
     */
    protected function fixMissingCategories(bool $dryRun, bool $debug): void
    {
        $this->newLine();
        $this->info('Checking for missing "files from agent" categories...');

        // Get all candidate IDs that have AgentCandidate records
        $agentCandidateIds = AgentCandidate::pluck('candidate_id')->unique();

        // Get candidate IDs that already have "files from agent" category
        $candidatesWithCategory = Category::where('nameOfCategory', 'files from agent')
            ->pluck('candidate_id')
            ->unique();

        // Find candidates missing the category
        $missingCategoryIds = $agentCandidateIds->diff($candidatesWithCategory);

        if ($missingCategoryIds->isEmpty()) {
            $this->info('All agent candidates have "files from agent" category.');
            return;
        }

        $this->warn('Found ' . $missingCategoryIds->count() . ' candidates missing "files from agent" category.');

        $created = 0;
        $this->output->progressStart($missingCategoryIds->count());

        foreach ($missingCategoryIds as $candidateId) {
            if (!$dryRun) {
                $category = new Category();
                $category->candidate_id = $candidateId;
                $category->nameOfCategory = 'files from agent';
                $category->isGenerated = 0;
                $category->save();

                // Attach required roles
                $category->visibleToRoles()->attach($this->requiredRoles);
            }
            $created++;

            if ($debug) {
                $this->newLine();
                $this->line("  Created category for candidate ID: {$candidateId}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info('Missing categories summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created' . ($dryRun ? ' (would be)' : ''), $created],
            ]
        );
    }

    /**
     * Fix files that have category_id pointing to a category from a different candidate
     */
    protected function fixFilesWithWrongCategoryId(bool $dryRun, bool $debug): void
    {
        $this->newLine();
        $this->info('Checking for files with wrong category_id...');

        // Get all "files from agent" categories indexed by candidate_id
        $categoriesByCandidate = Category::where('nameOfCategory', 'files from agent')
            ->pluck('id', 'candidate_id');

        // Get all "files from agent" category IDs
        $allAgentCategoryIds = Category::where('nameOfCategory', 'files from agent')
            ->pluck('id');

        if ($allAgentCategoryIds->isEmpty()) {
            $this->warn('No "files from agent" categories found.');
            return;
        }

        // Find files in "files from agent" categories where the category belongs to a different candidate
        $filesWithWrongCategory = File::whereIn('category_id', $allAgentCategoryIds)
            ->get()
            ->filter(function ($file) use ($categoriesByCandidate) {
                $correctCategoryId = $categoriesByCandidate->get($file->candidate_id);
                return $correctCategoryId && $file->category_id !== $correctCategoryId;
            });

        if ($filesWithWrongCategory->isEmpty()) {
            $this->info('All files have correct category_id.');
            return;
        }

        $this->warn('Found ' . $filesWithWrongCategory->count() . ' files with wrong category_id.');

        $fixed = 0;
        $skipped = 0;
        $this->output->progressStart($filesWithWrongCategory->count());

        foreach ($filesWithWrongCategory as $file) {
            $correctCategoryId = $categoriesByCandidate->get($file->candidate_id);

            if ($correctCategoryId) {
                if ($debug) {
                    $this->newLine();
                    $this->line("  File ID {$file->id} ({$file->fileName}): category_id {$file->category_id} -> {$correctCategoryId}");
                }

                if (!$dryRun) {
                    $file->category_id = $correctCategoryId;
                    $file->save();
                }
                $fixed++;
            } else {
                $skipped++;
                if ($debug) {
                    $this->newLine();
                    $this->warn("  File ID {$file->id}: No correct category found for candidate {$file->candidate_id}");
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info('Wrong category_id fix summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Fixed' . ($dryRun ? ' (would be)' : ''), $fixed],
                ['Skipped (no correct category)', $skipped],
            ]
        );
    }

    /**
     * Fix category permissions - add Agent and Nomad staff roles
     */
    protected function fixCategoryPermissions($categories, bool $dryRun): void
    {
        $this->newLine();
        $this->info('Fixing category permissions...');

        if ($categories->isEmpty()) {
            $this->warn('No categories to fix permissions for.');
            return;
        }

        $updated = 0;
        $alreadyCorrect = 0;

        $this->output->progressStart($categories->count());

        foreach ($categories as $category) {
            $existingRoles = $category->visibleToRoles()->pluck('roles.id')->toArray();
            $missingRoles = array_diff($this->requiredRoles, $existingRoles);

            if (empty($missingRoles)) {
                $alreadyCorrect++;
            } else {
                if (!$dryRun) {
                    $category->visibleToRoles()->syncWithoutDetaching($this->requiredRoles);
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

    /**
     * Fix contract_id on files
     */
    protected function fixContractIds($categories, bool $dryRun, bool $debug): void
    {
        $this->newLine();
        $this->info('Fixing contract_id on files...');

        $categoryIds = $categories->pluck('id', 'candidate_id');

        if ($categoryIds->isEmpty()) {
            $this->warn('No "files from agent" categories found.');
            return;
        }

        $this->info('Found ' . $categoryIds->count() . ' "files from agent" categories.');

        // Get files without contract_id in these categories
        $filesWithoutContract = File::whereIn('category_id', $categoryIds->values())
            ->whereNull('contract_id')
            ->get();

        if ($filesWithoutContract->isEmpty()) {
            $this->info('All files already have contract_id assigned.');
            return;
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
        $this->info('Contract ID fix summary:');
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
    }
}
