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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('fullName')->nullable();
            $table->string('fullNameCyrillic')->nullable();
            $table->string('birthday')->nullable();
            $table->string('placeOdBirth')->nullable();
            $table->string('country')->nullable();
            $table->string('area')->nullable();
            $table->string('areaOfResidence')->nullable();
            $table->string('addressOfResidence')->nullable();
            $table->string('periodOfResidence')->nullable();
            $table->string('passportValidUntil')->nullable();
            $table->string('passportIssuedBy')->nullable();
            $table->string('passportIssuedOn')->nullable();
            $table->string('addressOfWork')->nullable();
            $table->string('nameOfFacility')->nullable();
            $table->string('education')->nullable();
            $table->string('specialty')->nullable();
            $table->string('qualification')->nullable();
            $table->string('contractExtensionPeriod')->nullable();
            $table->string('salary')->nullable();
            $table->string('workingTime')->nullable();
            $table->string('workingDays')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('fullName');
            $table->dropColumn('fullNameCyrillic');
            $table->dropColumn('birthday');
            $table->dropColumn('placeOfBirth');
            $table->dropColumn('country');
            $table->dropColumn('area');
            $table->dropColumn('areaOfResidence');
            $table->dropColumn('addressOfResidence');
            $table->dropColumn('periodOfResidence');
            $table->dropColumn('passportValidUntil');
            $table->dropColumn('passportIssuedBy');
            $table->dropColumn('passportIssuedOn');
            $table->dropColumn('addressOfWork');
            $table->dropColumn('nameOfFacility');
            $table->dropColumn('education');
            $table->dropColumn('specialty');
            $table->dropColumn('qualification');
            $table->dropColumn('contractExtensionPeriod');
            $table->dropColumn('salary');
            $table->dropColumn('workingTime');
            $table->dropColumn('workingDays');
        });
    }
};
