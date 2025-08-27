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
        // Update existing 'standard' agreement types to 'erpr'
        DB::table('company_service_contracts')
            ->where('agreement_type', 'standard')
            ->update(['agreement_type' => 'erpr']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert 'erpr' back to 'standard' if needed
        DB::table('company_service_contracts')
            ->where('agreement_type', 'erpr')
            ->update(['agreement_type' => 'standard']);
    }
};