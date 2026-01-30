<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sets is_active = true for candidates that have only one contract.
     */
    public function up(): void
    {
        // Find all candidate_ids that have exactly one contract
        // and set is_active = true for those contracts
        DB::statement("
            UPDATE candidate_contracts cc
            INNER JOIN (
                SELECT candidate_id
                FROM candidate_contracts
                WHERE deleted_at IS NULL
                GROUP BY candidate_id
                HAVING COUNT(*) = 1
            ) single_contracts ON cc.candidate_id = single_contracts.candidate_id
            SET cc.is_active = 1
            WHERE cc.deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this migration
        // as we don't know the original state
    }
};
