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
        // Using raw SQL to modify enum since doctrine/dbal is not installed
        DB::statement("ALTER TABLE agent_invoices MODIFY COLUMN invoiceStatus ENUM('invoiced', 'not_invoiced', 'rejected', 'paid') DEFAULT 'not_invoiced'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE agent_invoices MODIFY COLUMN invoiceStatus ENUM('invoiced', 'not_invoiced', 'rejected') DEFAULT 'not_invoiced'");
    }
};
