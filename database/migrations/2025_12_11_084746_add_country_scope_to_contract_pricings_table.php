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
        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->enum('country_scope', [
                'all_countries',              // За всички държави (включително Индия и Непал)
                'india_nepal_only',           // Само за Индия и Непал
                'except_india_nepal'          // За всички държави ОСВЕН Индия и Непал
            ])->default('all_countries')->after('description')
              ->comment('Defines which countries this pricing applies to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->dropColumn('country_scope');
        });
    }
};
