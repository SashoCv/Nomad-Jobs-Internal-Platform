<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class DeleteFilesFromStorageForCandidatesThatAreDeleted extends Command
{
    protected $signature = 'candidates:delete-files';

    protected $description = 'Delete files from storage for candidates';

    public function handle()
    {
        // Наоѓаме сите кандидати со soft delete или со статус "Отказ"
        $candidates = Candidate::with('files', 'latestStatusHistory.status')
            ->whereNotNull('deleted_at')
            ->get();



        $this->info("Found {$candidates->count()} candidates.");

        foreach ($candidates as $candidate) {
            foreach ($candidate->files as $file) {
                $fullPath = storage_path('app/public/' . $file->filePath);

                if ($file->filePath && file_exists($fullPath)) {
                    unlink($fullPath);
                    $file->delete();
                    $this->info("Deleted file: {$file->filePath}");
                } else {
                    $this->warn("Skipped file (ID: {$file->id}) - does not exist: {$fullPath}");
                }
            }
        }

        $this->info("All files for these candidates have been deleted.");
        return Command::SUCCESS;
    }
}
