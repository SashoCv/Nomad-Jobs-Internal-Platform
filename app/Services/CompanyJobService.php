<?php

namespace App\Services;

use App\Models\AgentCandidate;
use App\Models\AssignedJob;
use App\Models\CompanyJob;
use App\Models\Company;
use App\Models\CompanyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompanyJobService
{
    public function __construct(
        private CompanyJobNotificationService $notificationService
    ) {}

    public function createCompanyJob(array $data, ?array $agentIds = null): CompanyJob
    {
        return DB::transaction(function () use ($data, $agentIds) {
            $companyJob = new CompanyJob();
            $this->fillCompanyJobData($companyJob, $data);
            $companyJob->save();

            $companyRequest = new CompanyRequest();
            $companyRequest->company_job_id = $companyJob->id;
            $companyRequest->approved = false;
            $companyRequest->save();

            $this->notificationService->sendCreatedNotification($companyJob);

            if ($agentIds && (Auth::user()->role_id == 1 || Auth::user()->role_id == 2)) {
                $this->assignAgentsToJob($companyJob, $agentIds);
            }

            return $companyJob;
        });
    }

    public function updateCompanyJob(CompanyJob $companyJob, array $data, ?array $agentIds = null): CompanyJob
    {
        return DB::transaction(function () use ($companyJob, $data, $agentIds) {
            $this->fillCompanyJobData($companyJob, $data);
            $companyJob->save();

            $this->notificationService->sendUpdatedNotification($companyJob);

            if ($agentIds !== null && (Auth::user()->role_id == 1 || Auth::user()->role_id == 2)) {
                $this->syncAgentsToJob($companyJob, $agentIds);
            }

            return $companyJob;
        });
    }

    public function deleteCompanyJob(CompanyJob $companyJob): bool
    {
        return DB::transaction(function () use ($companyJob) {
            $this->deleteRelatedCandidates($companyJob);
            return $companyJob->delete();
        });
    }

    public function enrichJobWithCompanyData(CompanyJob $companyJob): CompanyJob
    {
        $company = Company::find($companyJob->company_id);

        if ($company) {
            $companyJob->companyImage = $company->logoPath;
            $companyJob->companyCity = $company->companyCity;
            $companyJob->companyName = $company->nameOfCompany;
        }

        return $companyJob;
    }

    public function getAssignedAgentsForJob(CompanyJob $companyJob): array
    {
        return AssignedJob::where('company_job_id', $companyJob->id)
            ->pluck('user_id')
            ->toArray();
    }

    private function fillCompanyJobData(CompanyJob $companyJob, array $data): void
    {
        $companyJob->user_id = $data['user_id'] ?? Auth::id();
        $companyJob->company_id = $data['company_id'] ?? Auth::user()->company_id;
        $companyJob->job_title = $data['job_title'];
        $companyJob->number_of_positions = $data['number_of_positions'];
        $companyJob->job_description = $data['job_description'];
        // Mutator automatically sets contract_type_id when contract_type is set
        $companyJob->contract_type = !empty($data['contract_type']) ? $data['contract_type'] : null;
        $companyJob->requirementsForCandidates = !empty($data['requirementsForCandidates']) ? $data['requirementsForCandidates'] : null;
        $companyJob->salary = !empty($data['salary']) ? $data['salary'] : null;
        $companyJob->bonus = !empty($data['bonus']) ? $data['bonus'] : null;
        $companyJob->workTime = !empty($data['workTime']) ? $data['workTime'] : null;
        $companyJob->additionalWork = !empty($data['additionalWork']) ? $data['additionalWork'] : null;
        $companyJob->vacationDays = !empty($data['vacationDays']) ? $data['vacationDays'] : null;
        $companyJob->rent = !empty($data['rent']) ? $data['rent'] : null;
        $companyJob->food = !empty($data['food']) ? $data['food'] : null;
        $companyJob->otherDescription = !empty($data['otherDescription']) ? $data['otherDescription'] : null;
    }

    private function assignAgentsToJob(CompanyJob $companyJob, array $agentIds): void
    {
        foreach ($agentIds as $agentId) {
            $assignedJob = new AssignedJob();
            $assignedJob->user_id = $agentId;
            $assignedJob->company_job_id = $companyJob->id;
            $assignedJob->save();
        }
    }

    private function syncAgentsToJob(CompanyJob $companyJob, array $agentIds): void
    {
        if (empty($agentIds)) {
            return;
        }

        foreach ($agentIds as $agentId) {
            $exists = AssignedJob::where('user_id', $agentId)
                ->where('company_job_id', $companyJob->id)
                ->exists();

            if (!$exists) {
                $assignedJob = new AssignedJob();
                $assignedJob->user_id = $agentId;
                $assignedJob->company_job_id = $companyJob->id;
                $assignedJob->save();
            }
        }
    }

    private function deleteRelatedCandidates(CompanyJob $companyJob): void
    {
        $candidates = AgentCandidate::where('company_job_id', $companyJob->id)->get();

        foreach ($candidates as $candidate) {
            $candidate->delete();
        }
    }
}
