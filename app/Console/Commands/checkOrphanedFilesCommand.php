<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class checkOrphanedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:orphaned-files {--delete : Delete orphaned files automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for orphaned files in storage that are not in the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // промени ја патеката ако треба
        $storagePath = 'public/files';

        // сите фајлови од storage
        $allFiles = Storage::files($storagePath);

        // сите од база
        $dbFiles = DB::table('files')
            ->pluck('filePath')
            ->map(function ($path) {
                return str_replace('files/', 'public/files/', $path);
            })
            ->toArray();

        // orphaned (ги има во storage ама не во база)
        $orphanedFiles = array_diff($allFiles, $dbFiles);

        $this->info("Storage files: " . count($allFiles));
        $this->info("Database files: " . count($dbFiles));
        $this->warn("Orphaned files (in storage, not in DB): " . count($orphanedFiles));

//        if (!empty($orphanedFiles)) {
//            foreach ($orphanedFiles as $file) {
//                $this->line(" - " . $file);
//            }
//        }

        // ако пуштиш со --delete, ќе ги избрише
        if ($this->option('delete') && !empty($orphanedFiles)) {
            foreach ($orphanedFiles as $file) {
                Storage::delete($file);
            }
            $this->info("Deleted " . count($orphanedFiles) . " orphaned files.");
        }

        return Command::SUCCESS;
    }
}
