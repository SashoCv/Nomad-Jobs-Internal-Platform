<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates existing job postings to 'filled' status if they have
     * enough approved candidates (status_for_candidate_from_agent_id = 3)
     * to meet their number_of_positions requirement.
     */
    public function up(): void
    {
        // Get all active/inactive job postings that should be marked as filled
        $jobsToUpdate = DB::table('company_jobs')
            ->leftJoin('agent_candidates', function ($join) {
                $join->on('company_jobs.id', '=', 'agent_candidates.company_job_id')
                    ->where('agent_candidates.status_for_candidate_from_agent_id', '=', 3)
                    ->whereNull('agent_candidates.deleted_at');
            })
            ->select(
                'company_jobs.id',
                'company_jobs.number_of_positions',
                DB::raw('COUNT(agent_candidates.id) as approved_count')
            )
            ->whereIn('company_jobs.status', ['active', 'inactive'])
            ->whereNull('company_jobs.deleted_at')
            ->groupBy('company_jobs.id', 'company_jobs.number_of_positions')
            ->havingRaw('COUNT(agent_candidates.id) >= company_jobs.number_of_positions')
            ->get();

        // Update each job to 'filled' status
        foreach ($jobsToUpdate as $job) {
            DB::table('company_jobs')
                ->where('id', $job->id)
                ->update(['status' => 'filled']);
        }

        // Log how many jobs were updated
        if ($jobsToUpdate->count() > 0) {
            \Illuminate\Support\Facades\Log::info(
                "Updated {$jobsToUpdate->count()} job postings to 'filled' status based on approved candidates count."
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This is a data migration, reversing it would require
     * manually determining which jobs were previously active/inactive.
     * For safety, we don't automatically reverse this.
     */
    public function down(): void
    {
        // Reversing this migration is not straightforward since we don't know
        // the original status of each job posting. If needed, restore from backup
        // or manually update the specific records.
    }
};
