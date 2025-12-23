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

        // Migrate existing data based on company_requests.approved and showJob

        // Step 1: Job postings with unapproved requests → status = 'pending'
        DB::table('company_jobs')
            ->whereIn('id', function ($query) {
                $query->select('company_job_id')
                    ->from('company_requests')
                    ->where('approved', false)
                    ->whereNull('deleted_at');
            })
            ->update(['status' => 'pending']);

        // Step 2: Job postings with showJob = true AND (approved request OR no request) → status = 'active'
        DB::table('company_jobs')
            ->where('showJob', true)
            ->where('status', 'pending') // Only update those still pending (not caught by step 1)
            ->update(['status' => 'active']);

        // Step 3: Job postings with showJob = false AND (approved request OR no request) → status = 'inactive'
        DB::table('company_jobs')
            ->where('showJob', false)
            ->where('status', 'pending') // Only update those still pending
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
