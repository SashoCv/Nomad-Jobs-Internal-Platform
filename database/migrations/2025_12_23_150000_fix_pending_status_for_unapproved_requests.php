<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Jobs with unapproved company_requests should be 'pending' status.
     * The previous migration had a logic error where steps 2 and 3 overwrote step 1.
     */
    public function up(): void
    {
        // Set jobs with unapproved requests to 'pending' status
        // This must run AFTER the showJob-based updates to properly override them
        DB::table('company_jobs')
            ->whereIn('id', function ($query) {
                $query->select('company_job_id')
                    ->from('company_requests')
                    ->where('approved', false)
                    ->whereNull('deleted_at');
            })
            ->whereNull('deleted_at')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to inactive (safest default for unapproved jobs)
        DB::table('company_jobs')
            ->whereIn('id', function ($query) {
                $query->select('company_job_id')
                    ->from('company_requests')
                    ->where('approved', false)
                    ->whereNull('deleted_at');
            })
            ->whereNull('deleted_at')
            ->update(['status' => 'inactive']);
    }
};
