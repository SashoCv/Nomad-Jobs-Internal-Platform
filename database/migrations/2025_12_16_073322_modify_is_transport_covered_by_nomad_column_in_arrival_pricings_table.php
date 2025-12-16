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
        // Change column from boolean to tinyInteger to support 4 coverage options:
        // 0 = Client, 1 = Nomad, 2 = Agent, 3 = Candidate
        DB::statement('ALTER TABLE arrival_pricings MODIFY COLUMN isTransportCoveredByNomad TINYINT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert back to boolean
        DB::statement('ALTER TABLE arrival_pricings MODIFY COLUMN isTransportCoveredByNomad TINYINT(1) NULL');
    }
};
