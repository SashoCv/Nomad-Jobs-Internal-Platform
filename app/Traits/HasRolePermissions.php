<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\Permission;

trait HasRolePermissions
{
    /**
     * Check if the authenticated user has permission for a specific action
     */
    protected function checkPermission($permission)
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Use permission system for all roles
        return $user->hasPermission($permission);
    }

    /**
     * Check if user has any of the specified permissions
     */
    protected function checkAnyPermission($permissions)
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Use permission system for all roles
        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if user has specific role
     */
    protected function checkRole($roleId)
    {
        $user = Auth::user();
        return $user && $user->hasRole($roleId);
    }

    /**
     * Check if user has any of the specified roles
     */
    protected function checkAnyRole($roleIds)
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole($roleIds);
    }

    /**
     * Get permission-based response for unauthorized access
     */
    protected function unauthorizedResponse($message = 'Insufficient permissions')
    {
        return response()->json(['error' => $message], 403);
    }

    // Permission helper methods for all admin functions

    // Dashboard
    protected function canViewDashboard()
    {
        return $this->checkPermission(Permission::DASHBOARD_READ);
    }

    // Home
    protected function canViewHome()
    {
        return $this->checkPermission(Permission::HOME_READ);
    }

    protected function canFilterHome()
    {
        return $this->checkPermission(Permission::HOME_FILTER);
    }

    protected function canViewArrivals()
    {
        return $this->checkPermission(Permission::HOME_ARRIVALS);
    }

    protected function canChangeArrivalStatus()
    {
        return $this->checkPermission(Permission::HOME_CHANGE_STATUS);
    }

    // Companies
    protected function canViewCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_READ);
    }

    protected function canCreateCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_CREATE);
    }

    protected function canUpdateCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_UPDATE);
    }

    protected function canDeleteCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_DELETE);
    }

    // Industries
    protected function canViewIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_READ);
    }

    protected function canCreateIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_CREATE);
    }

    protected function canUpdateIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_UPDATE);
    }

    protected function canDeleteIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_DELETE);
    }

    // Contracts
    protected function canViewContracts()
    {
        return $this->checkPermission(Permission::COMPANIES_CONTRACTS_READ);
    }

    protected function canCreateContracts()
    {
        return $this->checkPermission(Permission::COMPANIES_CONTRACTS_CREATE);
    }

    protected function canUpdateContracts()
    {
        return $this->checkPermission(Permission::COMPANIES_CONTRACTS_UPDATE);
    }

    protected function canDeleteContracts()
    {
        return $this->checkPermission(Permission::COMPANIES_CONTRACTS_DELETE);
    }

    // Requests
    protected function canViewRequests()
    {
        return $this->checkPermission(Permission::COMPANY_JOB_REQUESTS_READ);
    }

    protected function canApproveRequests()
    {
        return $this->checkPermission(Permission::COMPANY_JOB_REQUESTS_APPROVE);
    }

    protected function canDeleteRequests()
    {
        return $this->checkPermission(Permission::COMPANY_JOB_REQUESTS_DELETE);
    }

    // Candidates
    protected function canViewCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_READ);
    }

    protected function canCreateCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_CREATE);
    }

    protected function canUpdateCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_UPDATE);
    }

    protected function canDeleteCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_DELETE);
    }

    // Agent Candidates
    protected function canViewAgentCandidates()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_READ);
    }

    protected function canChangeAgentCandidateStatus()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_CHANGE_STATUS);
    }

    protected function canCreateAgentCandidates()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_CREATE);
    }

    protected function canDeleteAgentCandidates()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_DELETE);
    }

    // Multi Applicant Generator
    protected function canAccessMultiApplicantGenerator()
    {
        return $this->checkPermission(Permission::MULTI_APPLICANT_GENERATOR);
    }

    // Expired Items
    protected function canViewExpiredContracts()
    {
        return $this->checkPermission(Permission::EXPIRED_CONTRACTS_READ);
    }

    protected function canViewExpiredMedicalInsurance()
    {
        return $this->checkPermission(Permission::EXPIRED_MEDICAL_INSURANCE_READ);
    }

    // Documents
    protected function canViewDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_READ);
    }

    protected function canCreateDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_CREATE);
    }

    protected function canUpdateDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_UPDATE);
    }

    protected function canDeleteDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_DELETE);
    }

    // Status History
    protected function canViewStatusHistory()
    {
        return $this->checkPermission(Permission::STATUS_HISTORY_READ);
    }

    // Users
    protected function canViewUsers()
    {
        return $this->checkPermission(Permission::USERS_READ);
    }

    protected function canCreateUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE);
    }

    protected function canUpdateUsers()
    {
        return $this->checkPermission(Permission::USERS_UPDATE);
    }

    protected function canDeleteUsers()
    {
        return $this->checkPermission(Permission::USERS_DELETE);
    }

    protected function canCreateCompanyUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE_COMPANIES);
    }

    protected function canCreateAgentUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE_AGENTS);
    }

    // Job Postings
    protected function canViewJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_READ);
    }

    protected function canCreateJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_CREATE);
    }

    protected function canUpdateJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_UPDATE);
    }

    protected function canDeleteJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_DELETE);
    }

    // Job Positions
    protected function canViewJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_READ);
    }

    protected function canCreateJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_CREATE);
    }

    protected function canUpdateJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_UPDATE);
    }

    protected function canDeleteJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_DELETE);
    }

    // Finances
    protected function canViewFinances()
    {
        return $this->checkPermission(Permission::FINANCE_READ);
    }

    protected function canCreateFinances()
    {
        return $this->checkPermission(Permission::FINANCE_CREATE);
    }

    protected function canUpdateFinances()
    {
        return $this->checkPermission(Permission::FINANCE_UPDATE);
    }

    protected function canDeleteFinances()
    {
        return $this->checkPermission(Permission::FINANCE_DELETE);
    }

    /**
     * Check if user is staff (admin/manager roles) - replacement for role_id == 1 || role_id == 2
     */
    protected function isStaff()
    {
        $user = Auth::user();
        if (!$user) return false;

        // Staff roles are internal company roles (not external users)
        return $user->hasAnyRole([
            Role::GENERAL_MANAGER,
            Role::MANAGER,
            Role::OFFICE,
            Role::HR,
            Role::OFFICE_MANAGER,
            Role::RECRUITERS,
            Role::FINANCE
        ]);
    }

    /**
     * Check if user is admin/manager (roles 1-2) - for admin-only features
     */
    protected function isAdminOrManager()
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole([Role::GENERAL_MANAGER, Role::MANAGER]);
    }

    /**
     * Check if user has full admin access (only General Manager)
     */
    protected function isFullAdmin()
    {
        $user = Auth::user();
        return $user && $user->hasRole(Role::GENERAL_MANAGER);
    }

    /**
     * Get all staff role IDs (for switch statements)
     */
    protected function getStaffRoleIds()
    {
        return [
            Role::GENERAL_MANAGER,
            Role::MANAGER,
            Role::OFFICE,
            Role::HR,
            Role::OFFICE_MANAGER,
            Role::RECRUITERS,
            Role::FINANCE
        ];
    }
}
