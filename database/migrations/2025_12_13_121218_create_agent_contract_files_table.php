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
        Schema::create('agent_contract_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_service_contract_id')->constrained('agent_service_contracts')->onDelete('cascade')->comment('Foreign key to agent service contracts');
            $table->string('filePath')->comment('Path to the stored file');
            $table->string('fileName')->comment('Original file name');
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
        Schema::dropIfExists('agent_contract_files');
    }
};
