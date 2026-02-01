<?php

use Illuminate\Database\Migrations\Migration;
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
        // Drop the existing foreign key constraint
        DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_company_service_contract_id_foreign');

        // Modify the column to be nullable
        DB::statement('ALTER TABLE invoices MODIFY company_service_contract_id BIGINT UNSIGNED NULL');

        // Re-add the foreign key constraint
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_company_service_contract_id_foreign FOREIGN KEY (company_service_contract_id) REFERENCES company_service_contracts(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the foreign key constraint
        DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_company_service_contract_id_foreign');

        // Make the column NOT NULL again (only works if no NULL values exist)
        DB::statement('ALTER TABLE invoices MODIFY company_service_contract_id BIGINT UNSIGNED NOT NULL');

        // Re-add the foreign key constraint
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_company_service_contract_id_foreign FOREIGN KEY (company_service_contract_id) REFERENCES company_service_contracts(id) ON DELETE CASCADE');
    }
};
