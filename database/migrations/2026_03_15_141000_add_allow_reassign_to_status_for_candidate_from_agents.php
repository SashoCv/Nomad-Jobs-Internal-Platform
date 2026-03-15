<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_for_candidate_from_agents', function (Blueprint $table) {
            $table->boolean('allow_reassign')->default(false)->after('show_for_companies');
        });
    }

    public function down(): void
    {
        Schema::table('status_for_candidate_from_agents', function (Blueprint $table) {
            $table->dropColumn('allow_reassign');
        });
    }
};
