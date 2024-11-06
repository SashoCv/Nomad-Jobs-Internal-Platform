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
        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->foreignId('status_for_candidate_from_agent_id')
                ->default(1) // Set the default
                ->constrained('status_for_candidate_from_agents') // Reference the correct table
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->dropForeign('fk_agent_candidates_status_for_candidate_from_agent_id');
            $table->dropColumn('status_for_candidate_from_agent_id');
        });
    }
};
