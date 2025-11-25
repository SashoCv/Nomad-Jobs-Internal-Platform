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
        Schema::create('administrative_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Име на административната позиција
            $table->string('name_bg')->nullable(); // Име на бугарски
            $table->text('description')->nullable(); // Опис
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
        Schema::dropIfExists('administrative_positions');
    }
};
