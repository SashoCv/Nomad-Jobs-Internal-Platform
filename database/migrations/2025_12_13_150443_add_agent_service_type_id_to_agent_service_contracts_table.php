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
        Schema::table('agent_service_contracts', function (Blueprint $table) {
            $table->foreignId('agent_service_type_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('agent_service_types')
                ->onDelete('cascade')
                ->comment('Type of service provided by the agent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_service_contracts', function (Blueprint $table) {
            $table->dropForeign(['agent_service_type_id']);
            $table->dropColumn('agent_service_type_id');
        });
    }
};
