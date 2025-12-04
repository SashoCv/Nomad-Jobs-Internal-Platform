<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobPostingsOverviewController extends Controller
{
    /**
     * Get job postings overview with statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get all companies with their job postings and candidate statistics
            $companies = Company::select('companies.id', 'companies.nameOfCompany as name')
                ->whereHas('companyJobs', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->with(['companyJobs' => function ($query) {
                    $query->select(
                        'company_jobs.id',
                        'company_jobs.company_id',
                        'company_jobs.job_title as title',
                        'company_jobs.employment_type as type',
                        'company_jobs.number_of_positions as positions',
                        'company_jobs.contract_type',
                        DB::raw('DATE_FORMAT(company_jobs.created_at, "%Y-%m-%d") as startDate'),
                        DB::raw('(CASE WHEN company_jobs.showJob = 1 THEN "active" ELSE "inactive" END) as status')
                    )
                    ->whereNull('deleted_at')
                    ->withCount([
                        'agentCandidates as candidates' => function ($query) {
                            $query->whereNull('deleted_at');
                        },
                        'agentCandidates as approved' => function ($query) {
                            $query->where('status_for_candidate_from_agent_id', 3)
                                ->whereNull('deleted_at');
                        }
                    ])
                    ->get()
                    ->map(function ($job) {
                        // Ensure count fields exist with default 0
                        $candidatesCount = $job->candidates ?? $job->candidates_count ?? 0;
                        $approvedCount = $job->approved ?? $job->approved_count ?? 0;

                        // Set as properties
                        $job->candidates = (int) $candidatesCount;
                        $job->approved = (int) $approvedCount;

                        // Calculate available positions: positions - approved
                        $job->available = max(0, (int) $job->positions - $job->approved);

                        // Remove contract_type from response (only used for backend logic)
                        unset($job->contract_type);

                        // Add endDate as null (can be enhanced later if needed)
                        $job->endDate = null;

                        return $job;
                    });
                }])
                ->get()
                ->map(function ($company) {
                    $jobs = $company->companyJobs;

                    return [
                        'id' => $company->id,
                        'companyName' => $company->name,
                        'totalPostings' => $jobs->count(),
                        'totalPositions' => $jobs->sum('positions'),
                        'totalCandidates' => $jobs->sum('candidates'),
                        'approvedCandidates' => $jobs->sum('approved'),
                        'availablePositions' => $jobs->sum('available'),
                        'jobPostings' => $jobs->toArray(),
                    ];
                })
                ->filter(function ($company) {
                    // Only return companies with at least one job posting
                    return $company['totalPostings'] > 0;
                })
                ->values();

            return response()->json($companies, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve job postings overview',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job postings overview for a specific company
     *
     * @param int $companyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($companyId)
    {
        try {
            $company = Company::select('companies.id', 'companies.nameOfCompany as name')
                ->where('companies.id', $companyId)
                ->with(['companyJobs' => function ($query) {
                    $query->select(
                        'company_jobs.id',
                        'company_jobs.company_id',
                        'company_jobs.job_title as title',
                        'company_jobs.employment_type as type',
                        'company_jobs.number_of_positions as positions',
                        'company_jobs.contract_type',
                        DB::raw('DATE_FORMAT(company_jobs.created_at, "%Y-%m-%d") as startDate'),
                        DB::raw('(CASE WHEN company_jobs.showJob = 1 THEN "active" ELSE "inactive" END) as status')
                    )
                    ->whereNull('deleted_at')
                    ->withCount([
                        'agentCandidates as candidates' => function ($query) {
                            $query->whereNull('deleted_at');
                        },
                        'agentCandidates as approved' => function ($query) {
                            $query->where('status_for_candidate_from_agent_id', 3)
                                ->whereNull('deleted_at');
                        }
                    ])
                    ->get()
                    ->map(function ($job) {
                        // Ensure count fields exist with default 0
                        $candidatesCount = $job->candidates ?? $job->candidates_count ?? 0;
                        $approvedCount = $job->approved ?? $job->approved_count ?? 0;

                        // Set as properties
                        $job->candidates = (int) $candidatesCount;
                        $job->approved = (int) $approvedCount;

                        // Calculate available positions: positions - approved
                        $job->available = max(0, (int) $job->positions - $job->approved);

                        unset($job->contract_type);
                        $job->endDate = null;
                        return $job;
                    });
                }])
                ->first();

            if (!$company) {
                return response()->json(['error' => 'Company not found'], 404);
            }

            $jobs = $company->companyJobs;

            $result = [
                'id' => $company->id,
                'companyName' => $company->name,
                'totalPostings' => $jobs->count(),
                'totalPositions' => $jobs->sum('positions'),
                'totalCandidates' => $jobs->sum('candidates'),
                'approvedCandidates' => $jobs->sum('approved'),
                'availablePositions' => $jobs->sum('available'),
                'jobPostings' => $jobs->toArray(),
            ];

            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve company job postings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
