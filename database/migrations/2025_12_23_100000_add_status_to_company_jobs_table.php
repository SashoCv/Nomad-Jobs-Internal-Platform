<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'inactive', 'filled', 'rejected'])
                ->default('pending')
                ->after('showJob');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->timestamp('published_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Migrate existing data based on showJob boolean
        // showJob = true → status = 'active'
        // showJob = false → status = 'inactive' (existing jobs are already approved)
        DB::table('company_jobs')
            ->where('showJob', true)
            ->update(['status' => 'active']);

        DB::table('company_jobs')
            ->where('showJob', false)
            ->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_at', 'approved_by', 'published_at']);
        });
    }
};
