<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 4: Drop legacy passport columns from candidates table.
     * All passport data is now stored in candidate_passports table.
     */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'passport',
                'passportValidUntil',
                'passportIssuedBy',
                'passportIssuedOn',
                'passportPath',
                'passportName',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('passport')->nullable();
            $table->date('passportValidUntil')->nullable();
            $table->string('passportIssuedBy')->nullable();
            $table->date('passportIssuedOn')->nullable();
            $table->string('passportPath')->nullable();
            $table->string('passportName')->nullable();
        });
    }
};
