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
        Schema::create('agent_contract_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_service_contract_id')->constrained('agent_service_contracts')->onDelete('cascade');
            $table->foreignId('agent_service_type_id')->constrained('agent_service_types')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();

            // Country scope: all, include, exclude
            $table->enum('countryScopeType', ['all', 'include', 'exclude'])->default('all');
            $table->json('countryScopeIds')->nullable(); // array of country IDs

            // Company scope: all, include, exclude
            $table->enum('companyScopeType', ['all', 'include', 'exclude'])->default('all');
            $table->json('companyScopeIds')->nullable(); // array of company IDs

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_contract_pricing');
    }
};
