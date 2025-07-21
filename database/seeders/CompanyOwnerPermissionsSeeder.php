<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class CompanyOwnerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Company Owner permissions - same as Company User
        // Limited business access for company owners
        $allowedPermissions = [
            // Candidates - ONLY VIEW
            Permission::CANDIDATES_VIEW,
            
            // Companies - VIEW and EDIT only (no create/delete)
            Permission::COMPANIES_VIEW,
            Permission::COMPANIES_EDIT,
            
            // Contracts - ONLY VIEW
            Permission::CONTRACTS_VIEW,
            
            // Requests - FULL CRUD
            Permission::REQUESTS_VIEW,
            Permission::REQUESTS_APPROVE,
            Permission::REQUESTS_DELETE,
            
            // Job Postings - VIEW and CREATE only (no edit/delete)
            Permission::JOB_POSTINGS_VIEW,
            Permission::JOB_POSTINGS_CREATE,
        ];

        $permissionIds = Permission::whereIn('slug', $allowedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Company Owner role (role_id = 5)
        $companyOwnerRole = Role::find(Role::COMPANY_OWNER);
        if ($companyOwnerRole) {
            $companyOwnerRole->permissions()->sync($permissionIds);
            
            echo "Company Owner permissions assigned successfully!\n";
            echo "Company Owner has access to " . count($permissionIds) . " permissions:\n";
            echo "- Candidates: VIEW only\n";
            echo "- Companies: VIEW + EDIT only\n";
            echo "- Contracts: VIEW only\n";
            echo "- Requests: FULL CRUD\n";
            echo "- Job Postings: VIEW + CREATE only\n";
            echo "Company Owner uses existing role-based logic for other functions.\n";
        } else {
            echo "Company Owner role not found!\n";
        }
    }
}