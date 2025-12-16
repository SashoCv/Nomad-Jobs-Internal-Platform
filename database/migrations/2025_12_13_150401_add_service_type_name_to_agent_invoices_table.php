<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agent_invoices', function (Blueprint $table) {
            $table->string('serviceTypeName')
                ->nullable()
                ->after('agent_service_contract_id')
                ->comment('Name of the service type from agent_service_types table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_invoices', function (Blueprint $table) {
            $table->dropColumn('serviceTypeName');
        });
    }
};
