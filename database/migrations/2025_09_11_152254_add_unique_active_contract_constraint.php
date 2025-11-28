<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, fix any existing companies with multiple active contracts
        // Keep the most recent (highest ID) active contract, expire others
        $duplicateActiveContracts = DB::select("
            SELECT company_id, COUNT(*) as count
            FROM company_service_contracts 
            WHERE status = 'active' AND deleted_at IS NULL
            GROUP BY company_id 
            HAVING count > 1
        ");

        foreach ($duplicateActiveContracts as $duplicate) {
            // Get all active contracts for this company, ordered by ID (newest first)
            $contracts = DB::select("
                SELECT id 
                FROM company_service_contracts 
                WHERE company_id = ? AND status = 'active' AND deleted_at IS NULL 
                ORDER BY id DESC
            ", [$duplicate->company_id]);

            // Skip the first (newest) contract, expire the rest
            $contractsToExpire = array_slice($contracts, 1);
            
            foreach ($contractsToExpire as $contract) {
                DB::update("
                    UPDATE company_service_contracts 
                    SET status = 'expired' 
                    WHERE id = ?
                ", [$contract->id]);
            }
        }

        // Check if the column already exists, if not create it
        $columnExists = DB::select("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'company_service_contracts' 
            AND COLUMN_NAME = 'active_company_constraint'
        ");

        if (empty($columnExists)) {
            // Create a computed column that is only populated for active contracts
            try {
                DB::statement("
                    ALTER TABLE company_service_contracts
                    ADD COLUMN active_company_constraint VARCHAR(255) GENERATED ALWAYS AS (
                        CASE
                            WHEN status = 'active' AND deleted_at IS NULL THEN CONCAT('active_', company_id)
                            ELSE NULL
                        END
                    ) STORED
                ");
            } catch (\Exception $e) {
                // If the generated column syntax fails, try with VIRTUAL instead of STORED
                DB::statement("
                    ALTER TABLE company_service_contracts
                    ADD COLUMN active_company_constraint VARCHAR(255) AS (
                        CASE
                            WHEN status = 'active' AND deleted_at IS NULL THEN CONCAT('active_', company_id)
                            ELSE NULL
                        END
                    ) VIRTUAL
                ");
            }
        }
        
        // Check if the index already exists, if not create it
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'company_service_contracts' 
            AND INDEX_NAME = 'unique_active_contract_per_company'
        ");

        if (empty($indexExists)) {
            // Create unique index on the generated column
            DB::statement("
                CREATE UNIQUE INDEX unique_active_contract_per_company 
                ON company_service_contracts(active_company_constraint)
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the unique index first
        DB::statement("DROP INDEX unique_active_contract_per_company ON company_service_contracts");
        
        // Drop the generated column
        DB::statement("ALTER TABLE company_service_contracts DROP COLUMN active_company_constraint");
    }
};