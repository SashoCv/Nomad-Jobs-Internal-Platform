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
        // Add is_qualified to candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('is_qualified')->default(false)->after('notes');
        });

        // Add qualification_scope to agent_contract_pricing table
        // Values: 'all' (default), 'qualified', 'unqualified'
        Schema::table('agent_contract_pricing', function (Blueprint $table) {
            $table->string('qualification_scope')->default('all')->after('companyScopeIds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('is_qualified');
        });

        Schema::table('agent_contract_pricing', function (Blueprint $table) {
            $table->dropColumn('qualification_scope');
        });
    }
};
