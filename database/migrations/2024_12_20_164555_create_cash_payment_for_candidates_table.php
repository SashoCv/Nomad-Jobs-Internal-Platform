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
        Schema::create('cash_payment_for_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('amount')->nullable();
            $table->text('purposeOfPayment')->nullable();
            $table->boolean('isPaid')->default(false);
            $table->date('paymentDate')->nullable();
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
        Schema::dropIfExists('cash_payment_for_candidates');
    }
};
