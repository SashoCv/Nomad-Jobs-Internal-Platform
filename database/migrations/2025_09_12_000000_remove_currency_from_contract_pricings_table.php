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
            $table->dropColumn('currency');
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
            $table->string('currency')->default('BGN')->comment('Currency of the price, default is BGN');
        });
    }
};