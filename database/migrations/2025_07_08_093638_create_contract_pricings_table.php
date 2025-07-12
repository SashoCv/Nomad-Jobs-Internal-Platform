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
        Schema::create('contract_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_service_contract_id')->constrained('company_service_contracts')->comment('Foreign key to the company service contracts table');
            $table->foreignId('contract_service_type_id')->constrained('contract_service_types')->comment('Foreign key to the contract service types table');
            $table->decimal('price', 10, 2)->comment('Price for the service type under the contract');
            $table->string('currency')->default('LEV')->comment('Currency of the price, default is LEV');
            $table->foreignId('status_id')->constrained('statuses')->comment('Foreign key to the statuses table');
            $table->text('description')->nullable()->comment('Description of the pricing details');
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
        Schema::dropIfExists('contract_pricings');
    }
};
