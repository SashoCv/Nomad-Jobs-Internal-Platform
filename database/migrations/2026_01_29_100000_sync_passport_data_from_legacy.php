<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Sync passport data from legacy candidates columns to candidate_passports table.
     *
     * This migration fixes two issues:
     * 1. 131 candidates created after Jan 22, 2026 that have passport data but no record in candidate_passports
     * 2. 50 records with mismatched data between tables (legacy columns have more recent/correct data)
     *
     * The legacy candidates table is used as the source of truth.
     */
    public function up(): void
    {
        // Step 1: Insert missing passport records (candidates without a passport record)
        $missingCount = DB::table('candidates as c')
            ->leftJoin('candidate_passports as cp', 'c.id', '=', 'cp.candidate_id')
            ->whereNull('cp.id')
            ->whereNull('c.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('c.passport')
                    ->orWhereNotNull('c.passportValidUntil')
                    ->orWhereNotNull('c.passportIssuedOn')
                    ->orWhereNotNull('c.passportIssuedBy')
                    ->orWhereNotNull('c.passportPath');
            })
            ->count();

        Log::info("Passport sync migration: Found {$missingCount} candidates without passport records");

        // Insert missing records
        DB::statement("
            INSERT INTO candidate_passports (candidate_id, passport_number, expiry_date, issue_date, issued_by, file_path, file_name, created_at, updated_at)
            SELECT
                c.id,
                c.passport,
                c.passportValidUntil,
                c.passportIssuedOn,
                c.passportIssuedBy,
                c.passportPath,
                c.passportName,
                NOW(),
                NOW()
            FROM candidates c
            LEFT JOIN candidate_passports cp ON c.id = cp.candidate_id
            WHERE cp.id IS NULL
              AND c.deleted_at IS NULL
              AND (
                  c.passport IS NOT NULL
                  OR c.passportValidUntil IS NOT NULL
                  OR c.passportIssuedOn IS NOT NULL
                  OR c.passportIssuedBy IS NOT NULL
                  OR c.passportPath IS NOT NULL
              )
        ");

        Log::info("Passport sync migration: Inserted missing passport records");

        // Step 2: Update mismatched records (use legacy as source of truth)
        $mismatchedCount = DB::table('candidates as c')
            ->join('candidate_passports as cp', 'c.id', '=', 'cp.candidate_id')
            ->whereNull('c.deleted_at')
            ->where(function ($query) {
                $query->whereRaw("COALESCE(c.passport, '') != COALESCE(cp.passport_number, '')")
                    ->orWhereRaw("COALESCE(c.passportValidUntil, '1900-01-01') != COALESCE(cp.expiry_date, '1900-01-01')")
                    ->orWhereRaw("COALESCE(c.passportIssuedOn, '1900-01-01') != COALESCE(cp.issue_date, '1900-01-01')")
                    ->orWhereRaw("COALESCE(c.passportIssuedBy, '') != COALESCE(cp.issued_by, '')");
            })
            ->count();

        Log::info("Passport sync migration: Found {$mismatchedCount} mismatched records to update");

        // Update mismatched records
        DB::statement("
            UPDATE candidate_passports cp
            INNER JOIN candidates c ON c.id = cp.candidate_id
            SET
                cp.passport_number = c.passport,
                cp.expiry_date = c.passportValidUntil,
                cp.issue_date = c.passportIssuedOn,
                cp.issued_by = c.passportIssuedBy,
                cp.file_path = COALESCE(cp.file_path, c.passportPath),
                cp.file_name = COALESCE(cp.file_name, c.passportName),
                cp.updated_at = NOW()
            WHERE c.deleted_at IS NULL
        ");

        Log::info("Passport sync migration: Updated all passport records from legacy data");

        // Verification queries
        $stillMissing = DB::table('candidates as c')
            ->leftJoin('candidate_passports as cp', 'c.id', '=', 'cp.candidate_id')
            ->whereNull('cp.id')
            ->whereNull('c.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('c.passport')
                    ->orWhereNotNull('c.passportValidUntil');
            })
            ->count();

        $stillMismatched = DB::table('candidates as c')
            ->join('candidate_passports as cp', 'c.id', '=', 'cp.candidate_id')
            ->whereNull('c.deleted_at')
            ->where(function ($query) {
                $query->whereRaw("COALESCE(c.passport, '') != COALESCE(cp.passport_number, '')")
                    ->orWhereRaw("COALESCE(c.passportValidUntil, '1900-01-01') != COALESCE(cp.expiry_date, '1900-01-01')")
                    ->orWhereRaw("COALESCE(c.passportIssuedOn, '1900-01-01') != COALESCE(cp.issue_date, '1900-01-01')");
            })
            ->count();

        Log::info("Passport sync migration completed", [
            'still_missing' => $stillMissing,
            'still_mismatched' => $stillMismatched,
        ]);

        if ($stillMissing > 0 || $stillMismatched > 0) {
            Log::warning("Passport sync migration: Some records may still need attention", [
                'still_missing' => $stillMissing,
                'still_mismatched' => $stillMismatched,
            ]);
        }
    }

    /**
     * This migration syncs data and cannot be safely rolled back.
     */
    public function down(): void
    {
        // This migration syncs data from legacy to new table.
        // Rolling back would require restoring from backup.
        Log::warning('Passport sync migration rollback requested - no action taken. Restore from backup if needed.');
    }
};
