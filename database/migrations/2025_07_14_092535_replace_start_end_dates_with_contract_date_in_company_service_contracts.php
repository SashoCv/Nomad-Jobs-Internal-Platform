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
        Schema::table('company_service_contracts', function (Blueprint $table) {
            $table->date('contractDate')->nullable()->after('status');
            $table->dropColumn(['startDate', 'endDate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_service_contracts', function (Blueprint $table) {
            $table->date('startDate')->nullable();
            $table->date('endDate')->nullable();
            $table->dropColumn('contractDate');
        });
    }
};
