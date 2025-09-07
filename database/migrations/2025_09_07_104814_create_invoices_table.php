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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_service_contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_service_type_id')->constrained()->onDelete('cascade');
            $table->string('statusName');
            $table->date('statusDate');
            $table->float('price', 10, 2);
            $table->enum('invoiceStatus', ['invoiced', 'not_invoiced', 'rejected'])->default('not_invoiced');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
