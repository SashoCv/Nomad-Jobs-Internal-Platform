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

    /**
     * Helper method to check if user can view companies
     */
    protected function canViewCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_VIEW);
    }

    /**
     * Helper method to check if user can create companies
     */
    protected function canCreateCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_CREATE);
    }

    /**
     * Helper method to check if user can edit companies
     */
    protected function canEditCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_EDIT);
    }

    /**
     * Helper method to check if user can delete companies
     */
    protected function canDeleteCompanies()
    {
        return $this->checkPermission(Permission::COMPANIES_DELETE);
    }

    /**
     * Helper method to check if user can access company contracts
     */
    protected function canAccessCompanyContracts()
    {
        return $this->checkPermission(Permission::COMPANIES_CONTRACTS);
    }

    /**
     * Helper method to check if user can view users
     */
    protected function canViewUsers()
    {
        return $this->checkPermission(Permission::USERS_VIEW);
    }

    /**
     * Helper method to check if user can create company users only
     */
    protected function canCreateCompanyUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE_COMPANIES);
    }

    /**
     * Helper method to check if user can create agent users only
     */
    protected function canCreateAgentUsers()
    {
        return $this->checkPermission(Permission::USERS_CREATE_AGENTS);
    }

    /**
     * Helper method to check if user can view candidates
     */
    protected function canViewCandidates()
    {
        return $this->checkPermission(Permission::CANDIDATES_VIEW);
    }

    /**
     * Helper method to check if user can create/edit candidates
     */
    protected function canManageCandidates()
    {
        return $this->checkAnyPermission([
            Permission::CANDIDATES_CREATE,
            Permission::CANDIDATES_EDIT,
            Permission::CANDIDATES_DELETE
        ]);
    }

    /**
     * Helper method to check if user can view job posts
     */
    protected function canViewJobs()
    {
        return $this->checkPermission(Permission::JOBS_VIEW);
    }

    /**
     * Helper method to check if user can manage job posts
     */
    protected function canManageJobs()
    {
        return $this->checkAnyPermission([
            Permission::JOBS_CREATE,
            Permission::JOBS_EDIT,
            Permission::JOBS_DELETE
        ]);
    }

    /**
     * Helper method to check if user can view finance
     */
    protected function canViewFinance()
    {
        return $this->checkPermission(Permission::FINANCE_VIEW);
    }

    /**
     * Helper method to check if user can manage finance
     */
    protected function canManageFinance()
    {
        return $this->checkAnyPermission([
            Permission::FINANCE_CREATE,
            Permission::FINANCE_EDIT,
            Permission::FINANCE_DELETE
        ]);
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