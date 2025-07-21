<?php

namespace App\Services;

use App\Models\AssignedJob;
use App\Models\CompanyJob;
use App\Models\UserOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CompanyJobQueryService
{
    public function getJobsForUser(?string $contractType = null, ?int $companyId = null)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        $query = $this->buildBaseQuery();

        if ($contractType) {
            $query->where('company_jobs.contract_type', $contractType);
        }

        $this->applyRoleBasedFilters($query, $roleId, $user, $companyId);

        return $query->paginate();
    }

    public function findJobForUser(int $jobId): ?CompanyJob
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        switch ($roleId) {
            case 1:
            case 2:
            case 5:
                return $this->findJobForAdminOrOwner($jobId, $roleId, $user);
            case 3:
                return $this->findJobForCompanyUser($jobId, $user);
            case 4:
                return $this->findJobForAgent($jobId, $user);
            default:
                return null;
        }
    }

    private function buildBaseQuery(): QueryBuilder
    {
        return DB::table('company_jobs')
            ->join('companies', 'company_jobs.company_id', '=', 'companies.id')
            ->select(
                'company_jobs.id',
                'companies.logoPath',
                'companies.companyCity',
                'company_jobs.company_id',
                'company_jobs.job_title',
                'company_jobs.number_of_positions',
                'company_jobs.contract_type',
                'company_jobs.job_description',
                'companies.nameOfCompany',
                'company_jobs.created_at',
                'company_jobs.updated_at',
                'company_jobs.deleted_at'
            )
            ->whereNull('company_jobs.deleted_at')
            ->orderBy('company_jobs.created_at', 'desc');
    }

    private function applyRoleBasedFilters(QueryBuilder $query, int $roleId, $user, ?int $companyId = null): void
    {
        switch ($roleId) {
            case 1:
            case 2:
                if ($companyId) {
                    $query->where('companies.id', $companyId);
                }
                break;

            case 3:
                $query->where('company_jobs.company_id', $user->company_id);
                break;

            case 5:
                $companyIds = UserOwner::where('user_id', $user->id)
                    ->pluck('company_id')
                    ->toArray();
                $query->whereIn('company_jobs.company_id', $companyIds);
                break;

            case 4:
                $companyJobIds = AssignedJob::where('user_id', $user->id)
                    ->pluck('company_job_id')
                    ->toArray();
                $query->whereIn('company_jobs.id', $companyJobIds);
                break;
        }
    }

    private function findJobForAdminOrOwner(int $jobId, int $roleId, $user): ?CompanyJob
    {
        $query = CompanyJob::where('id', $jobId);

        if ($roleId === 5) {
            $companyIds = UserOwner::where('user_id', $user->id)
                ->pluck('company_id')
                ->toArray();
            $query->whereIn('company_id', $companyIds);
        }

        return $query->first();
    }

    private function findJobForCompanyUser(int $jobId, $user): ?CompanyJob
    {
        return CompanyJob::where('id', $jobId)
            ->where('company_id', $user->company_id)
            ->first();
    }

    private function findJobForAgent(int $jobId, $user): ?CompanyJob
    {
        $assignedJob = AssignedJob::where('user_id', $user->id)
            ->where('company_job_id', $jobId)
            ->first();

        if (!$assignedJob) {
            return null;
        }

        return CompanyJob::find($jobId);
    }
}