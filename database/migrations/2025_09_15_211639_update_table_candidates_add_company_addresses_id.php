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
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('company_adresses_id')->nullable();
            $table->foreign('company_adresses_id')->references('id')->on('company_adresses')->onDelete('set null');
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
            $table->dropForeign(['company_adresses_id']);
            $table->dropColumn('company_adresses_id');
        });
    }
};
