<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupPassportDuplicates extends Command
{
    protected $signature = 'passport:cleanup
                            {--dry-run : Show what would be moved without actually moving}
                            {--batch=0 : Process only N files (0 = all)}';

    protected $description = 'Move original passport files to backup folder after migration (100% safe - no deletion)';

    private int $moved = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        $this->info($isDryRun ? '🔍 DRY RUN MODE - No files will be moved' : '🚀 LIVE MODE - Files will be moved to backup');
        $this->newLine();

        // Get all passport records that have file_path set
        $query = DB::table('candidate_passports')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '');

        $total = $query->count();
        $this->info("Found {$total} passport records with files");

        if ($batchSize > 0) {
            $query->limit($batchSize);
            $this->info("Processing batch of {$batchSize} files");
        }

        $passports = $query->get();
        $bar = $this->output->createProgressBar($passports->count());
        $bar->start();

        foreach ($passports as $passport) {
            $this->processPassport($passport, $isDryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files moved to backup', $this->moved],
                ['Skipped (no original or already cleaned)', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to actually move files.');
        }

        return Command::SUCCESS;
    }

    private function processPassport(object $passport, bool $isDryRun): void
    {
        $newPath = $passport->file_path; // e.g., candidate/123/passport/file.pdf

        // Find the original file in files table
        $originalFile = DB::table('files')
            ->where('candidate_id', $passport->candidate_id)
            ->where(function ($query) {
                $query->where('fileName', 'LIKE', '%passport%')
                    ->orWhere('fileName', 'LIKE', '%паспорт%')
                    ->orWhere('fileName', 'LIKE', '%Passport%')
                    ->orWhere('fileName', 'LIKE', '%Паспорт%');
            })
            ->orderBy('id', 'desc')
            ->first();

        if (!$originalFile || empty($originalFile->filePath)) {
            $this->skipped++;
            return;
        }

        $oldPath = $originalFile->filePath;

        // Skip if old path equals new path (shouldn't happen, but safety check)
        if ($oldPath === $newPath) {
            $this->skipped++;
            return;
        }

        // Check if new file exists (the copy we made during migration)
        if (!Storage::disk('public')->exists($newPath)) {
            $this->error("New file missing: {$newPath}");
            $this->errors++;
            return;
        }

        // Check if original file exists
        if (!Storage::disk('public')->exists($oldPath)) {
            // Already cleaned up or never existed
            $this->skipped++;
            return;
        }

        // Verify file sizes match (integrity check)
        $newSize = Storage::disk('public')->size($newPath);
        $oldSize = Storage::disk('public')->size($oldPath);

        if ($newSize !== $oldSize) {
            $this->error("Size mismatch for candidate {$passport->candidate_id}: new={$newSize}, old={$oldSize}");
            $this->errors++;
            return;
        }

        // Move to backup folder
        $backupPath = "backup/passports/{$oldPath}";

        if (!$isDryRun) {
            try {
                // Ensure backup directory exists
                $backupDir = dirname($backupPath);
                if (!Storage::disk('public')->exists($backupDir)) {
                    Storage::disk('public')->makeDirectory($backupDir);
                }

                // Move file to backup
                Storage::disk('public')->move($oldPath, $backupPath);
                $this->moved++;
            } catch (\Exception $e) {
                $this->error("Failed to move {$oldPath}: " . $e->getMessage());
                $this->errors++;
            }
        } else {
            $this->moved++;
        }
    }
}
