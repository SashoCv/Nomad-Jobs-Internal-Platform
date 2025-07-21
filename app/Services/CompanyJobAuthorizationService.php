<?php

namespace App\Services;

use App\Models\AssignedJob;
use App\Models\CompanyJob;
use App\Models\UserOwner;
use Illuminate\Support\Facades\Auth;

class CompanyJobAuthorizationService
{
    public function canCreateJob(): bool
    {
        $roleId = Auth::user()->role_id;
        return in_array($roleId, [1, 2, 3, 5]);
    }

    public function canUpdateJob(CompanyJob $companyJob): bool
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        switch ($roleId) {
            case 1:
            case 2:
                return true;
            case 3:
                return $companyJob->company_id === $user->company_id;
            case 5:
                return $this->isOwnerOfCompany($user->id, $companyJob->company_id);
            default:
                return false;
        }
    }

    public function canDeleteJob(): bool
    {
        $roleId = Auth::user()->role_id;
        return in_array($roleId, [1, 2]);
    }

    public function canViewJob(CompanyJob $companyJob): bool
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        switch ($roleId) {
            case 1:
            case 2:
                return true;
            case 3:
                return $companyJob->company_id === $user->company_id;
            case 4:
                return $this->isAgentAssignedToJob($user->id, $companyJob->id);
            case 5:
                return $this->isOwnerOfCompany($user->id, $companyJob->company_id);
            default:
                return false;
        }
    }

    public function canAssignAgents(): bool
    {
        $roleId = Auth::user()->role_id;
        return in_array($roleId, [1, 2]);
    }

    public function getCompanyIdForRole(): ?int
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        switch ($roleId) {
            case 1:
            case 2:
                return null;
            case 3:
                return $user->company_id;
            case 5:
                return null;
            default:
                return null;
        }
    }

    private function isOwnerOfCompany(int $userId, int $companyId): bool
    {
        return UserOwner::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
    }

    private function isAgentAssignedToJob(int $userId, int $jobId): bool
    {
        return AssignedJob::where('user_id', $userId)
            ->where('company_job_id', $jobId)
            ->exists();
    }
}