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
            $table->unsignedBigInteger('nomad_office_id')->nullable();
            $table->foreign('nomad_office_id')->references('id')->on('users')->onDelete('cascade');
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
            $table->dropForeign(['nomad_office_id']);
            $table->dropColumn('nomad_office_id');
        });
    }
};
