<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique constraint on candidate_id to ensure each candidate
     * can only have one passport record.
     */
    public function up(): void
    {
        Schema::table('candidate_passports', function (Blueprint $table) {
            // Each candidate can only have one passport record
            $table->unique('candidate_id', 'candidate_passport_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_passports', function (Blueprint $table) {
            $table->dropUnique('candidate_passport_unique');
        });
    }
};
