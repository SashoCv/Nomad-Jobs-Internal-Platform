<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->index('status_id');
            $table->index('company_id');
            $table->index('agent_id');
            $table->index('user_id');
            $table->index('case_id');
            $table->index('contract_type_id');
        });

        Schema::table('statushistories', function (Blueprint $table) {
            $table->index(['candidate_id', 'statusDate']);
            $table->index('status_id');
        });

        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->index(['user_id', 'deleted_at']);
            $table->index(['candidate_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['status_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['case_id']);
            $table->dropIndex(['contract_type_id']);
        });

        Schema::table('statushistories', function (Blueprint $table) {
            $table->dropIndex(['candidate_id', 'statusDate']);
            $table->dropIndex(['status_id']);
        });

        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'deleted_at']);
            $table->dropIndex(['candidate_id', 'deleted_at']);
        });
    }
};
