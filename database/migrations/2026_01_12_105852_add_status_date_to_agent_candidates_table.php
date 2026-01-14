<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->dateTime('status_date')->nullable()->after('status_for_candidate_from_agent_id');
        });

        // Set initial status_date from updated_at for existing records
        DB::statement('UPDATE agent_candidates SET status_date = updated_at WHERE status_date IS NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->dropColumn('status_date');
        });
    }
};
