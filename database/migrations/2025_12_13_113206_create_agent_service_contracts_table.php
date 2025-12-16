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
        Schema::create('agent_service_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade')->comment('Foreign key to the users table (agent)');
            $table->string('contractNumber')->nullable()->unique()->comment('Unique contract number');
            $table->enum('status', ['pending', 'active', 'expired', 'terminated'])->default('active')->comment('Status of the contract');
            $table->date('startDate')->nullable()->comment('Start date of the contract');
            $table->date('endDate')->nullable()->comment('End date of the contract (optional)');
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
        Schema::dropIfExists('agent_service_contracts');
    }
};
