<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes passport records that were created without file data.
     * Finds passport files from the files table and copies them to the new location.
     */
    public function up(): void
    {
        // Get all candidate_passports records that don't have a file
        $passports = DB::table('candidate_passports')
            ->whereNull('file_path')
            ->get();

        foreach ($passports as $passport) {
            // Find passport file from files table
            // Look for files with 'passport' or 'паспорт' in the filename
            $passportFile = DB::table('files')
                ->where('candidate_id', $passport->candidate_id)
                ->where(function ($query) {
                    $query->where('fileName', 'LIKE', '%passport%')
                        ->orWhere('fileName', 'LIKE', '%паспорт%')
                        ->orWhere('fileName', 'LIKE', '%Passport%')
                        ->orWhere('fileName', 'LIKE', '%Паспорт%');
                })
                ->orderBy('id', 'desc') // Get the most recent one
                ->first();

            if (!$passportFile || empty($passportFile->filePath)) {
                continue;
            }

            $oldPath = $passportFile->filePath;

            // Check if the file exists
            if (!Storage::disk('public')->exists($oldPath)) {
                continue;
            }

            // Create new path: candidate/{id}/passport/filename
            $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
            $originalName = $passportFile->fileName;

            // Clean up the filename (remove _passport suffix if present from generated docs)
            $newFileName = preg_replace('/_passport$/', '', $originalName);
            if (!$newFileName) {
                $newFileName = 'passport.' . $extension;
            }

            $newFilePath = "candidate/{$passport->candidate_id}/passport/{$newFileName}";

            // Copy file to new location (don't delete original as it's still in files table)
            Storage::disk('public')->copy($oldPath, $newFilePath);

            // Update the passport record
            DB::table('candidate_passports')
                ->where('id', $passport->id)
                ->update([
                    'file_path' => $newFilePath,
                    'file_name' => $newFileName,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only adds file data, no need to reverse
        // The files will remain in the new location
    }
};
