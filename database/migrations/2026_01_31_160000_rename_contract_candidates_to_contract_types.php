<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop the existing foreign key on candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['contract_candidates_id']);
        });

        // Step 2: Rename the table
        Schema::rename('contract_candidates', 'contract_types');

        // Step 3: Rename the column in candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->renameColumn('contract_candidates_id', 'contract_type_id');
        });

        // Step 4: Re-add the foreign key with new names
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->nullOnDelete();
        });

        // Step 5: Add contract_type_id to company_jobs
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_type_id')->nullable()->after('contract_type');
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->nullOnDelete();
        });

        // Step 6: Add contract_type_id to candidate_contracts
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_type_id')->nullable()->after('contract_type');
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Remove FK from candidate_contracts
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn('contract_type_id');
        });

        // Remove FK from company_jobs
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn('contract_type_id');
        });

        // Drop FK on candidates
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
        });

        // Rename column back
        Schema::table('candidates', function (Blueprint $table) {
            $table->renameColumn('contract_type_id', 'contract_candidates_id');
        });

        // Rename table back
        Schema::rename('contract_types', 'contract_candidates');

        // Re-add original FK
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreign('contract_candidates_id')->references('id')->on('contract_candidates')->nullOnDelete();
        });
    }
};
