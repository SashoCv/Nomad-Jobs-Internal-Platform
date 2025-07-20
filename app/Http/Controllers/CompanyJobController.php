<?php

namespace App\Http\Controllers;

use App\Models\CompanyJob;
use App\Models\User;
use App\Services\CompanyJobService;
use App\Services\CompanyJobQueryService;
use App\Services\CompanyJobAuthorizationService;
use App\Http\Requests\StoreCompanyJobRequest;
use App\Http\Requests\UpdateCompanyJobRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyJobController extends Controller
{
    public function __construct(
        private CompanyJobService $companyJobService,
        private CompanyJobQueryService $companyJobQueryService,
        private CompanyJobAuthorizationService $companyJobAuthorizationService
    ) {}


    public function index(Request $request)
    {
        try {
            $contractType = $request->contract_type;
            $companyId = $request->company_id;

            $allJobPostings = $this->companyJobQueryService->getJobsForUser($contractType, $companyId);

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $allJobPostings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to retrieve jobs",
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCompanyJobRequest $request)
    {
        if (!$this->companyJobAuthorizationService->canCreateJob()) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        try {
            $data = $request->all();
            $data['company_id'] = $data['company_id'] ?? $this->companyJobAuthorizationService->getCompanyIdForRole();

            $agentIds = $request->agentsIds;
            $companyJob = $this->companyJobService->createCompanyJob($data, $agentIds);

            return response()->json([
                "status" => "success",
                "message" => "Job created successfully",
                "data" => $companyJob,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Job creation failed",
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $companyJob = $this->companyJobQueryService->findJobForUser($id);

            if (!$companyJob) {
                return response()->json([
                    "status" => "error",
                    "message" => "Job not found or unauthorized access",
                ], 404);
            }

            if (!$this->companyJobAuthorizationService->canViewJob($companyJob)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized access",
                ], 403);
            }

            $companyJob = $this->companyJobService->enrichJobWithCompanyData($companyJob);

            if ($this->companyJobAuthorizationService->canAssignAgents()) {
                $companyJob->agentsIds = $this->companyJobService->getAssignedAgentsForJob($companyJob);
            }

            return response()->json([
                "status" => "success",
                "message" => "Job retrieved successfully",
                "data" => $companyJob
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to retrieve job",
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyJob $companyJob)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCompanyJobRequest $request, CompanyJob $companyJob)
    {
        $companyJob = CompanyJob::find($request->id);

        if (!$companyJob) {
            return response()->json([
                "status" => "error",
                "message" => "Job not found",
            ], 404);
        }

        if (!$this->companyJobAuthorizationService->canUpdateJob($companyJob)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        try {
            $data = $request->all();
            $agentIds = $request->agentsIds;

            $companyJob = $this->companyJobService->updateCompanyJob($companyJob, $data, $agentIds);

            return response()->json([
                "status" => "success",
                "message" => "Job updated successfully",
                "data" => $companyJob
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Job update failed",
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyJob  $companyJob
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!$this->companyJobAuthorizationService->canDeleteJob()) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }

        try {
            $companyJob = CompanyJob::find($id);

            if (!$companyJob) {
                return response()->json([
                    "status" => "error",
                    "message" => "Job not found",
                ], 404);
            }

            $this->companyJobService->deleteCompanyJob($companyJob);

            return response()->json([
                "status" => "success",
                "message" => "Job deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Job deletion failed",
            ], 500);
        }
    }
}
