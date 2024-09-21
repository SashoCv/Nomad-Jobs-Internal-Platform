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
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->date('arrival_date');
            $table->string('arrival_time');
            $table->string('arrival_location');
            $table->string('arrival_flight')->nullable();
            $table->string('where_to_stay')->nullable();
            $table->boolean('is_arrived')->default(false);
            $table->softDeletes();
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
        Schema::dropIfExists('arrivals');
    }
};
