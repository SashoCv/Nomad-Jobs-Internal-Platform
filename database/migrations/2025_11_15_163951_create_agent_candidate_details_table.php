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
        Schema::create('agent_candidate_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_candidate_id')->constrained('agent_candidates')->onDelete('cascade');
            $table->boolean('powerOfAttorney')->default(false)->comment('Пълномощно');
            $table->boolean('personnelReferences')->default(false)->comment('Справки за персонала');
            $table->boolean('accommodationAddress')->default(false)->comment('Адрес за настаняване');
            $table->text('notes')->nullable()->comment('Бележки');
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
        Schema::dropIfExists('agent_candidate_details');
    }
};
