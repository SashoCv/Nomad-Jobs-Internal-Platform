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
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->text('requirementsForCandidates')->nullable();
            $table->float('salary')->nullable();
            $table->float('bonus')->nullable();
            $table->string('workTime')->nullable();
            $table->text('additionalWork')->nullable();
            $table->string('vacationDays')->nullable();
            $table->text('rent')->nullable();
            $table->text('food')->nullable();
            $table->text('otherDescription')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropColumn('requirementsForCandidates');
            $table->dropColumn('salary');
            $table->dropColumn('bonus');
            $table->dropColumn('workTime');
            $table->dropColumn('additionalWork');
            $table->dropColumn('vacationDays');
            $table->dropColumn('rent');
            $table->dropColumn('food');
            $table->dropColumn('otherDescription');
        });
    }
};
