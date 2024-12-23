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
        Schema::create('arrival_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained()->onDelete('cascade');
            $table->foreignId('status_arrival_id')->constrained()->onDelete('cascade');
            $table->string('status_description')->nullable();
            $table->string('status_date')->nullable();
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
        Schema::dropIfExists('arrival_candidates');
    }
};
