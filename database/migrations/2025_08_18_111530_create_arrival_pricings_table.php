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
        Schema::create('arrival_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained('arrivals')->onDelete('cascade');
            $table->float('price', 8, 2)->nullable();
            $table->decimal('margin', 5, 2)->nullable();
            $table->float('total')->nullable();
            $table->boolean('billed')->default(false);
            $table->boolean('isTransportCoveredByNomad')->nullable();

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
        Schema::dropIfExists('arrival_pricings');
    }
};
