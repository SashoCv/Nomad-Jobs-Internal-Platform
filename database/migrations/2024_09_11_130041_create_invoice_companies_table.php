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
        Schema::create('invoice_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->string('status');
            $table->decimal('invoice_amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->boolean('is_paid')->default(false);
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
        Schema::dropIfExists('invoice_companies');
    }
};
