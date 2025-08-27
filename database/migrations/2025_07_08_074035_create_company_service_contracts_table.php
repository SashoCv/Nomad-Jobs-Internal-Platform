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
        Schema::create('company_service_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contractNumber')->nullable()->unique()->comment('Unique contract number');
            $table->string('agreement_type')->default('erpr')->comment('Type of the agreement, options: erpr, 90days');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->comment('Foreign key to the companies table');
            $table->string('status')->default('active')->comment('Status of the contract (e.g.,pending, active, expired, terminated)');
            $table->date('startDate')->comment('Start date of the contract');
            $table->date('endDate')->nullable()->comment('End date of the contract');
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
        Schema::dropIfExists('company_service_contracts');
    }
};
