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
        Schema::create('asign_candidate_to_nomad_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->references('id')->on('users');
            $table->foreignId('nomad_office_id')->references('id')->on('users');
            $table->foreignId('candidate_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asign_candidate_to_nomad_offices');
    }
};
