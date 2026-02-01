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
        // Pivot table: links agent_contract_pricing to contract_types
        // If no entries exist for a pricing → applies to ALL contract types
        // If entries exist → applies ONLY to those specific contract types
        Schema::create('agent_pricing_contract_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_id');
            $table->unsignedBigInteger('contract_type_id');
            $table->timestamps();

            $table->foreign('pricing_id', 'apct_pricing_fk')
                ->references('id')->on('agent_contract_pricing')
                ->onDelete('cascade');
            $table->foreign('contract_type_id', 'apct_contract_type_fk')
                ->references('id')->on('contract_types')
                ->onDelete('cascade');

            // Unique constraint to prevent duplicates
            $table->unique(['pricing_id', 'contract_type_id'], 'apct_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_pricing_contract_types');
    }
};
