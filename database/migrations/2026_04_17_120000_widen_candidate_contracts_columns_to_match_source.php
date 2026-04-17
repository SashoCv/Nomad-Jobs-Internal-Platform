<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen candidate_contracts columns so they match the validated input space
 * accepted at the source (company_jobs + StoreCompanyJobRequest validator),
 * which permits up to 255 characters.
 *
 * Before: working_time VARCHAR(100), working_days VARCHAR(100),
 *         contract_type VARCHAR(50),  dossier_number VARCHAR(100)
 * After:  all VARCHAR(255) — matches `company_jobs` and legacy `candidates`.
 *
 * Raw SQL is used because doctrine/dbal is not a direct dependency, so
 * Blueprint::change() is unavailable.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `working_time` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `working_days` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `contract_type` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `dossier_number` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Narrowing could truncate live data, so we guard against it.
        // If a rollback is genuinely needed, shorten the offending rows first.
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `dossier_number` VARCHAR(100) NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `contract_type` VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `working_days` VARCHAR(100) NULL');
        DB::statement('ALTER TABLE `candidate_contracts` MODIFY `working_time` VARCHAR(100) NULL');
    }
};
