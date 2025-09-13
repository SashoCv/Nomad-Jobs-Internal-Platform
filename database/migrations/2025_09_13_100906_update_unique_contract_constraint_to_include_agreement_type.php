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
        // Add a computed column for the constraint that includes agreement_type
        DB::statement("
            ALTER TABLE company_service_contracts 
            ADD COLUMN active_company_agreement_constraint VARCHAR(255) NULL
        ");
        
        // Create a trigger to maintain the constraint column
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
        
        // Create unique index on the constraint column
        DB::statement("
            CREATE UNIQUE INDEX unique_active_contract_per_company_agreement 
            ON company_service_contracts(active_company_agreement_constraint)
        ");
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
