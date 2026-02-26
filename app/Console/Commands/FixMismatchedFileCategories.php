<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMismatchedFileCategories extends Command
{
    protected $signature = 'fix:mismatched-file-categories
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Fix files whose category_id points to a category owned by a different candidate';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Fix Mismatched File Categories ===');
        $this->info('');

        // Find all files where the category belongs to a different candidate
        $mismatched = DB::select("
            SELECT f.id as file_id, f.candidate_id, f.fileName,
                   c.id as wrong_cat_id, c.candidate_id as wrong_cat_owner, c.nameOfCategory
            FROM files f
            JOIN categories c ON f.category_id = c.id
            WHERE f.candidate_id IS NOT NULL
              AND c.candidate_id IS NOT NULL
              AND f.candidate_id <> c.candidate_id
            ORDER BY f.candidate_id, c.nameOfCategory
        ");

        $total = count($mismatched);
        $this->info("Found {$total} mismatched files");

        if ($total === 0) {
            $this->info('Nothing to fix.');
            return Command::SUCCESS;
        }

        $reassigned = 0;
        $created = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($mismatched as $row) {
            // Try to find an existing category with the same name under the correct candidate
            $correctCat = DB::table('categories')
                ->where('candidate_id', $row->candidate_id)
                ->where('nameOfCategory', $row->nameOfCategory)
                ->first();

            if ($correctCat) {
                // Category already exists — reassign the file
                if (!$dryRun) {
                    DB::table('files')
                        ->where('id', $row->file_id)
                        ->update(['category_id' => $correctCat->id]);
                }
                $reassigned++;
            } else {
                // Need to create a new category for this candidate
                // Copy visibility (category_role) from the wrong category
                $wrongCatRoles = DB::table('category_role')
                    ->where('category_id', $row->wrong_cat_id)
                    ->pluck('role_id')
                    ->toArray();

                if (!$dryRun) {
                    $newCatId = DB::table('categories')->insertGetId([
                        'candidate_id' => $row->candidate_id,
                        'nameOfCategory' => $row->nameOfCategory,
                        'isGenerated' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Copy visibility roles
                    foreach ($wrongCatRoles as $roleId) {
                        DB::table('category_role')->insert([
                            'category_id' => $newCatId,
                            'role_id' => $roleId,
                        ]);
                    }

                    // Reassign the file
                    DB::table('files')
                        ->where('id', $row->file_id)
                        ->update(['category_id' => $newCatId]);
                }
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== Summary ===');
        $action = $dryRun ? 'Would reassign' : 'Reassigned';
        $this->info("{$action} to existing category: {$reassigned} files");

        $action = $dryRun ? 'Would create new category + reassign' : 'Created new category + reassigned';
        $this->info("{$action}: {$created} files");

        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }

        $this->info("Total: " . ($reassigned + $created) . " / {$total}");

        return Command::SUCCESS;
    }
}
