<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\Permission;

trait HasRolePermissions
{
    /**
     * Check if the authenticated user has permission for a specific action
     * Preserves existing logic for roles 3, 4, 5 (company user, agent, company owner)
     */
    protected function checkPermission($permission, $preserveExistingLogic = true)
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // For roles 3, 4, 5 (company user, agent, company owner) - preserve existing logic
        if ($preserveExistingLogic && $user->hasAnyRole([Role::COMPANY_USER, Role::AGENT, Role::COMPANY_OWNER])) {
            return true; // Allow controller to handle existing logic
        }

        // For new roles (1, 2, 6-10) - use permission system
        return $user->hasPermission($permission);
    }

    /**
     * Check if user has any of the specified permissions
     */
    protected function checkAnyPermission($permissions, $preserveExistingLogic = true)
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // For roles 3, 4, 5 (company user, agent, company owner) - preserve existing logic
        if ($preserveExistingLogic && $user->hasAnyRole([Role::COMPANY_USER, Role::AGENT, Role::COMPANY_OWNER])) {
            return true; // Allow controller to handle existing logic
        }

        // For new roles (1, 2, 6-10) - use permission system
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
        return $this->checkPermission(Permission::DASHBOARD_VIEW);
    }
    
    // Home
    protected function canViewHome()
    {
        return $this->checkPermission(Permission::HOME_VIEW);
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
        return $this->checkPermission(Permission::COMPANIES_VIEW);
    }
    
    protected function canCreateCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_CREATE);
    }
    
    protected function canEditCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_EDIT);
    }
    
    protected function canDeleteCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_DELETE);
    }
    
    // Industries
    protected function canViewIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_VIEW);
    }
    
    protected function canCreateIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_CREATE);
    }
    
    protected function canEditIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_EDIT);
    }
    
    protected function canDeleteIndustries()
    {
        return $this->checkPermission(Permission::INDUSTRIES_DELETE);
    }
    
    // Contracts
    protected function canViewContracts()
    {
        return $this->checkPermission(Permission::CONTRACTS_VIEW);
    }
    
    protected function canCreateContracts()
    {
        return $this->checkPermission(Permission::CONTRACTS_CREATE);
    }
    
    protected function canEditContracts()
    {
        return $this->checkPermission(Permission::CONTRACTS_EDIT);
    }
    
    protected function canDeleteContracts()
    {
        return $this->checkPermission(Permission::CONTRACTS_DELETE);
    }
    
    // Requests
    protected function canViewRequests()
    {
        return $this->checkPermission(Permission::REQUESTS_VIEW);
    }
    
    protected function canApproveRequests()
    {
        return $this->checkPermission(Permission::REQUESTS_APPROVE);
    }
    
    protected function canDeleteRequests()
    {
        return $this->checkPermission(Permission::REQUESTS_DELETE);
    }
    
    // Candidates
    protected function canViewCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_VIEW);
    }
    
    protected function canCreateCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_CREATE);
    }
    
    protected function canEditCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_EDIT);
    }
    
    protected function canDeleteCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_DELETE);
    }
    
    // Agent Candidates
    protected function canViewAgentCandidates()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_VIEW);
    }
    
    protected function canChangeAgentCandidateStatus()
    {
        return $this->checkPermission(Permission::AGENT_CANDIDATES_CHANGE_STATUS);
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
        return $this->checkPermission(Permission::EXPIRED_CONTRACTS_VIEW);
    }
    
    protected function canViewExpiredMedicalInsurance()
    {
        return $this->checkPermission(Permission::EXPIRED_MEDICAL_INSURANCE_VIEW);
    }
    
    // Documents
    protected function canViewDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_VIEW);
    }
    
    protected function canCreateDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_CREATE);
    }
    
    protected function canEditDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_EDIT);
    }
    
    protected function canDeleteDocuments()
    {
        return $this->checkPermission(Permission::DOCUMENTS_DELETE);
    }
    
    // Status History
    protected function canViewStatusHistory()
    {
        return $this->checkPermission(Permission::STATUS_HISTORY_VIEW);
    }
    
    // Users
    protected function canViewUsers()
    {
        return $this->checkPermission(Permission::USERS_VIEW);
    }
    
    protected function canCreateUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE);
    }
    
    protected function canEditUsers()
    {
        return $this->checkPermission(Permission::USERS_EDIT);
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
        return $this->checkPermission(Permission::JOB_POSTINGS_VIEW);
    }
    
    protected function canCreateJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_CREATE);
    }
    
    protected function canEditJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_EDIT);
    }
    
    protected function canDeleteJobPostings()
    {
        return $this->checkPermission(Permission::JOB_POSTINGS_DELETE);
    }
    
    // Job Positions
    protected function canViewJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_VIEW);
    }
    
    protected function canCreateJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_CREATE);
    }
    
    protected function canEditJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_EDIT);
    }
    
    protected function canDeleteJobPositions()
    {
        return $this->checkPermission(Permission::JOB_POSITIONS_DELETE);
    }
    
    // Finances
    protected function canViewFinances()
    {
        return $this->checkPermission(Permission::FINANCES_VIEW);
    }
    
    protected function canCreateFinances()
    {
        return $this->checkPermission(Permission::FINANCES_CREATE);
    }
    
    protected function canEditFinances()
    {
        return $this->checkPermission(Permission::FINANCES_EDIT);
    }
    
    protected function canDeleteFinances()
    {
        return $this->checkPermission(Permission::FINANCES_DELETE);
    }

    /**
     * Check if user is staff (admin/manager roles) - replacement for role_id == 1 || role_id == 2
     */
    protected function isStaff()
    {
        $user = Auth::user();
        if (!$user) return false;

        // For roles 3, 4, 5 (company user, agent, company owner) - preserve existing logic
        if ($user->hasAnyRole([Role::COMPANY_USER, Role::AGENT, Role::COMPANY_OWNER])) {
            return false; // These are not staff roles
        }

        // For roles 1, 2, 6-10 - check if they have basic admin permissions
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