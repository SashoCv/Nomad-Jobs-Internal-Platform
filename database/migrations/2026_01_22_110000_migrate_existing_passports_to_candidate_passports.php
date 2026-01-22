<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing passport data from candidates table and files table
     * to the new candidate_passports table.
     */
    public function up(): void
    {
        // Get all candidates that have passport dates
        $candidates = DB::table('candidates')
            ->where(function ($query) {
                $query->whereNotNull('passportValidUntil')
                    ->orWhereNotNull('passportIssuedOn');
            })
            ->get();

        foreach ($candidates as $candidate) {
            // Skip if no meaningful passport data
            if (empty($candidate->passportValidUntil) && empty($candidate->passportIssuedOn)) {
                continue;
            }

            // Check if a passport record already exists for this candidate
            $existingPassport = DB::table('candidate_passports')
                ->where('candidate_id', $candidate->id)
                ->first();

            if ($existingPassport) {
                // Skip if already migrated
                continue;
            }

            // Find passport file from files table
            // Look for files with 'passport' or 'паспорт' in the filename
            $passportFile = DB::table('files')
                ->where('candidate_id', $candidate->id)
                ->where(function ($query) {
                    $query->where('fileName', 'LIKE', '%passport%')
                        ->orWhere('fileName', 'LIKE', '%паспорт%')
                        ->orWhere('fileName', 'LIKE', '%Passport%')
                        ->orWhere('fileName', 'LIKE', '%Паспорт%');
                })
                ->orderBy('id', 'desc') // Get the most recent one
                ->first();

            $newFilePath = null;
            $newFileName = null;

            if ($passportFile && !empty($passportFile->filePath)) {
                $oldPath = $passportFile->filePath;

                // Check if the file exists
                if (Storage::disk('public')->exists($oldPath)) {
                    // Create new path: candidate/{id}/passport/filename
                    $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                    $originalName = $passportFile->fileName;

                    // Clean up the filename (remove _passport suffix if present from generated docs)
                    $newFileName = preg_replace('/_passport$/', '', $originalName);
                    if (!$newFileName) {
                        $newFileName = 'passport.' . $extension;
                    }

                    $newFilePath = "candidate/{$candidate->id}/passport/{$newFileName}";

                    // Copy file to new location (don't delete original as it's still in files table)
                    Storage::disk('public')->copy($oldPath, $newFilePath);
                }
            }

            // Create the new passport record
            DB::table('candidate_passports')->insert([
                'candidate_id' => $candidate->id,
                'passport_number' => $candidate->passport ?? null,
                'issue_date' => $candidate->passportIssuedOn,
                'expiry_date' => $candidate->passportValidUntil,
                'issued_by' => $candidate->passportIssuedBy ?? null,
                'file_path' => $newFilePath,
                'file_name' => $newFileName,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This migration cannot be safely rolled back as it migrates
     * existing data. Rolling back would delete all passport records including
     * those created after the migration.
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'This migration cannot be safely rolled back. ' .
            'It migrates existing passport data from candidates table to candidate_passports table. ' .
            'Rolling back would delete all passport records, including those created after migration. ' .
            'If you need to rollback, please handle it manually with proper data backup.'
        );
    }
};
