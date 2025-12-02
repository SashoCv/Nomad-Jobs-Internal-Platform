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
        // Check if column already exists
        $columnExists = DB::select("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'company_service_contracts'
            AND COLUMN_NAME = 'active_company_agreement_constraint'
        ");

        if (empty($columnExists)) {
            // Add a computed column for the constraint that includes agreement_type
            DB::statement("
                ALTER TABLE company_service_contracts
                ADD COLUMN active_company_agreement_constraint VARCHAR(255) NULL
            ");
        }
        
        // Check if triggers already exist
        $trigger1Exists = DB::select("
            SELECT TRIGGER_NAME
            FROM INFORMATION_SCHEMA.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            AND TRIGGER_NAME = 'update_active_constraint_trigger'
        ");

        $trigger2Exists = DB::select("
            SELECT TRIGGER_NAME
            FROM INFORMATION_SCHEMA.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            AND TRIGGER_NAME = 'update_active_constraint_update_trigger'
        ");

        // Create triggers only if they don't exist
        if (empty($trigger1Exists)) {
            DB::statement("
                CREATE TRIGGER update_active_constraint_trigger
                BEFORE INSERT ON company_service_contracts
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'active' AND NEW.deleted_at IS NULL THEN
                        SET NEW.active_company_agreement_constraint = CONCAT('active_', NEW.company_id, '_', NEW.agreement_type);
                    ELSE
                        SET NEW.active_company_agreement_constraint = NULL;
                    END IF;
                END
            ");
        }

        if (empty($trigger2Exists)) {
            DB::statement("
                CREATE TRIGGER update_active_constraint_update_trigger
                BEFORE UPDATE ON company_service_contracts
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'active' AND NEW.deleted_at IS NULL THEN
                        SET NEW.active_company_agreement_constraint = CONCAT('active_', NEW.company_id, '_', NEW.agreement_type);
                    ELSE
                        SET NEW.active_company_agreement_constraint = NULL;
                    END IF;
                END
            ");
        }
        
        // Update existing records
        DB::statement("
            UPDATE company_service_contracts
            SET active_company_agreement_constraint =
                CASE
                    WHEN status = 'active' AND deleted_at IS NULL
                    THEN CONCAT('active_', company_id, '_', agreement_type)
                    ELSE NULL
                END
        ");

        // Check if index already exists
        $indexExists = DB::select("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'company_service_contracts'
            AND INDEX_NAME = 'unique_active_contract_per_company_agreement'
        ");

        if (empty($indexExists)) {
            // Create unique index on the constraint column
            DB::statement("
                CREATE UNIQUE INDEX unique_active_contract_per_company_agreement
                ON company_service_contracts(active_company_agreement_constraint)
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
        // Drop the unique index
        DB::statement("DROP INDEX unique_active_contract_per_company_agreement ON company_service_contracts");
        
        // Drop triggers
        DB::statement("DROP TRIGGER IF EXISTS update_active_constraint_trigger");
        DB::statement("DROP TRIGGER IF EXISTS update_active_constraint_update_trigger");
        
        // Drop the constraint column
        DB::statement("ALTER TABLE company_service_contracts DROP COLUMN active_company_agreement_constraint");
    }
};
