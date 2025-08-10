<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'Dashboard View', 'slug' => Permission::DASHBOARD_VIEW, 'module' => 'dashboard'],
            
            // Home
            ['name' => 'Home View', 'slug' => Permission::HOME_VIEW, 'module' => 'home'],
            ['name' => 'Home Filter', 'slug' => Permission::HOME_FILTER, 'module' => 'home'],
            ['name' => 'Home Arrivals', 'slug' => Permission::HOME_ARRIVALS, 'module' => 'home'],
            ['name' => 'Home Change Status', 'slug' => Permission::HOME_CHANGE_STATUS, 'module' => 'home'],
            
            // Companies
            ['name' => 'Companies View', 'slug' => Permission::COMPANIES_VIEW, 'module' => 'companies'],
            ['name' => 'Companies Create', 'slug' => Permission::COMPANIES_CREATE, 'module' => 'companies'],
            ['name' => 'Companies Edit', 'slug' => Permission::COMPANIES_EDIT, 'module' => 'companies'],
            ['name' => 'Companies Delete', 'slug' => Permission::COMPANIES_DELETE, 'module' => 'companies'],
            
            // Industries
            ['name' => 'Industries View', 'slug' => Permission::INDUSTRIES_VIEW, 'module' => 'industries'],
            ['name' => 'Industries Create', 'slug' => Permission::INDUSTRIES_CREATE, 'module' => 'industries'],
            ['name' => 'Industries Edit', 'slug' => Permission::INDUSTRIES_EDIT, 'module' => 'industries'],
            ['name' => 'Industries Delete', 'slug' => Permission::INDUSTRIES_DELETE, 'module' => 'industries'],
            
            // Contracts
            ['name' => 'Contracts View', 'slug' => Permission::CONTRACTS_VIEW, 'module' => 'contracts'],
            ['name' => 'Contracts Create', 'slug' => Permission::CONTRACTS_CREATE, 'module' => 'contracts'],
            ['name' => 'Contracts Edit', 'slug' => Permission::CONTRACTS_EDIT, 'module' => 'contracts'],
            ['name' => 'Contracts Delete', 'slug' => Permission::CONTRACTS_DELETE, 'module' => 'contracts'],
            
            // Requests
            ['name' => 'Requests View', 'slug' => Permission::REQUESTS_VIEW, 'module' => 'requests'],
            ['name' => 'Requests Approve', 'slug' => Permission::REQUESTS_APPROVE, 'module' => 'requests'],
            ['name' => 'Requests Delete', 'slug' => Permission::REQUESTS_DELETE, 'module' => 'requests'],
            
            // Candidates
            ['name' => 'Candidates View', 'slug' => Permission::CANDIDATES_VIEW, 'module' => 'candidates'],
            ['name' => 'Candidates Create', 'slug' => Permission::CANDIDATES_CREATE, 'module' => 'candidates'],
            ['name' => 'Candidates Edit', 'slug' => Permission::CANDIDATES_EDIT, 'module' => 'candidates'],
            ['name' => 'Candidates Delete', 'slug' => Permission::CANDIDATES_DELETE, 'module' => 'candidates'],
            
            // Agent Candidates
            ['name' => 'Candidates From Agent View', 'slug' => Permission::CANDIDATES_FROM_AGENT_READ, 'module' => 'candidates_from_agent'],
            ['name' => 'Candidates From Agent Change Status', 'slug' => Permission::CANDIDATES_FROM_AGENT_CHANGE_STATUS, 'module' => 'candidates_from_agent'],
            ['name' => 'Candidates From Agent Delete', 'slug' => Permission::CANDIDATES_FROM_AGENT_DELETE, 'module' => 'candidates_from_agent'],
            
            // Multi Applicant Generator
            ['name' => 'Multi Applicant Generator', 'slug' => Permission::MULTI_APPLICANT_GENERATOR, 'module' => 'multi_applicant'],
            
            // Expired Items
            ['name' => 'Expired Contracts View', 'slug' => Permission::EXPIRED_CONTRACTS_VIEW, 'module' => 'expired_items'],
            ['name' => 'Expired Medical Insurance View', 'slug' => Permission::EXPIRED_MEDICAL_INSURANCE_VIEW, 'module' => 'expired_items'],
            
            // Documents
            ['name' => 'Documents View', 'slug' => Permission::DOCUMENTS_VIEW, 'module' => 'documents'],
            ['name' => 'Documents Create', 'slug' => Permission::DOCUMENTS_CREATE, 'module' => 'documents'],
            ['name' => 'Documents Edit', 'slug' => Permission::DOCUMENTS_EDIT, 'module' => 'documents'],
            ['name' => 'Documents Delete', 'slug' => Permission::DOCUMENTS_DELETE, 'module' => 'documents'],
            
            // Status History
            ['name' => 'Status History View', 'slug' => Permission::STATUS_HISTORY_VIEW, 'module' => 'status_history'],
            
            // Users
            ['name' => 'Users View', 'slug' => Permission::USERS_VIEW, 'module' => 'users'],
            ['name' => 'Users Create', 'slug' => Permission::USERS_CREATE, 'module' => 'users'],
            ['name' => 'Users Edit', 'slug' => Permission::USERS_EDIT, 'module' => 'users'],
            ['name' => 'Users Delete', 'slug' => Permission::USERS_DELETE, 'module' => 'users'],
            
            // Job Postings
            ['name' => 'Job Postings View', 'slug' => Permission::JOB_POSTINGS_VIEW, 'module' => 'job_postings'],
            ['name' => 'Job Postings Create', 'slug' => Permission::JOB_POSTINGS_CREATE, 'module' => 'job_postings'],
            ['name' => 'Job Postings Edit', 'slug' => Permission::JOB_POSTINGS_EDIT, 'module' => 'job_postings'],
            ['name' => 'Job Postings Delete', 'slug' => Permission::JOB_POSTINGS_DELETE, 'module' => 'job_postings'],
            
            // Job Positions
            ['name' => 'Job Positions View', 'slug' => Permission::JOB_POSITIONS_VIEW, 'module' => 'job_positions'],
            ['name' => 'Job Positions Create', 'slug' => Permission::JOB_POSITIONS_CREATE, 'module' => 'job_positions'],
            ['name' => 'Job Positions Edit', 'slug' => Permission::JOB_POSITIONS_EDIT, 'module' => 'job_positions'],
            ['name' => 'Job Positions Delete', 'slug' => Permission::JOB_POSITIONS_DELETE, 'module' => 'job_positions'],
            
            // Finances
            ['name' => 'Finances View', 'slug' => Permission::FINANCES_VIEW, 'module' => 'finances'],
            ['name' => 'Finances Create', 'slug' => Permission::FINANCES_CREATE, 'module' => 'finances'],
            ['name' => 'Finances Edit', 'slug' => Permission::FINANCES_EDIT, 'module' => 'finances'],
            ['name' => 'Finances Delete', 'slug' => Permission::FINANCES_DELETE, 'module' => 'finances'],
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']], 
                $permission
            );
        }

        // Assign all permissions to Admin role (role_id = 1)
        $adminRole = Role::find(Role::GENERAL_MANAGER);
        if ($adminRole) {
            $permissionIds = Permission::pluck('id')->toArray();
            $adminRole->permissions()->sync($permissionIds);
        }
    }
}