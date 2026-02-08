<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->enum('candidate_source', ['agent', 'direct_employer', 'assistance_only'])
                  ->nullable()
                  ->after('agent_id');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->enum('candidate_source', ['agent', 'direct_employer', 'assistance_only'])
                  ->nullable()
                  ->after('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('candidate_source');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropColumn('candidate_source');
        });
    }
};
