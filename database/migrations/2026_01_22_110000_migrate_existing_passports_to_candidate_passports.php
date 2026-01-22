<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing passport data from candidates table to the new candidate_passports table.
     */
    public function up(): void
    {
        // Get all candidates that have any passport data
        $candidates = DB::table('candidates')
            ->whereNotNull('passport')
            ->orWhereNotNull('passportValidUntil')
            ->orWhereNotNull('passportIssuedBy')
            ->orWhereNotNull('passportIssuedOn')
            ->orWhereNotNull('passportPath')
            ->orWhereNotNull('passportName')
            ->get();

        foreach ($candidates as $candidate) {
            // Skip if no meaningful passport data
            if (
                empty($candidate->passport) &&
                empty($candidate->passportValidUntil) &&
                empty($candidate->passportPath)
            ) {
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

            // Handle file migration if there's a passport file
            $newFilePath = null;
            $newFileName = $candidate->passportName;

            if (!empty($candidate->passportPath)) {
                $oldPath = $candidate->passportPath;

                // Check if the file exists
                if (Storage::disk('public')->exists($oldPath)) {
                    // Create new path: candidate/{id}/passport/filename
                    $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                    $newFileName = $candidate->passportName ?: ('passport.' . $extension);
                    $newFilePath = "candidate/{$candidate->id}/passport/{$newFileName}";

                    // Copy file to new location
                    Storage::disk('public')->copy($oldPath, $newFilePath);
                } else {
                    // File doesn't exist, just keep the reference
                    $newFilePath = $oldPath;
                }
            }

            // Create the new passport record
            DB::table('candidate_passports')->insert([
                'candidate_id' => $candidate->id,
                'passport_number' => $candidate->passport,
                'issue_date' => $candidate->passportIssuedOn,
                'expiry_date' => $candidate->passportValidUntil,
                'issued_by' => $candidate->passportIssuedBy,
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
