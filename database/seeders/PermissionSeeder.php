<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create all permissions
        $permissions = [
            ['name' => Permission::DASHBOARD_VIEW, 'slug' => 'dashboard.view', 'description' => 'View dashboard'],
            // Companies
            ['name' => Permission::COMPANIES_VIEW, 'slug' => 'companies.view', 'description' => 'View companies'],
            ['name' => Permission::COMPANIES_CREATE, 'slug' => 'companies.create', 'description' => 'Create companies'],
            ['name' => Permission::COMPANIES_EDIT, 'slug' => 'companies.edit', 'description' => 'Edit companies'],
            ['name' => Permission::COMPANIES_DELETE, 'slug' => 'companies.delete', 'description' => 'Delete companies'],
            ['name' => Permission::COMPANIES_CONTRACTS, 'slug' => 'companies.contracts', 'description' => 'Manage company contracts'],

            // Users
            ['name' => Permission::USERS_VIEW, 'slug' => 'users.view', 'description' => 'View users'],
            ['name' => Permission::USERS_CREATE, 'slug' => 'users.create', 'description' => 'Create users'],
            ['name' => Permission::USERS_EDIT, 'slug' => 'users.edit', 'description' => 'Edit users'],
            ['name' => Permission::USERS_DELETE, 'slug' => 'users.delete', 'description' => 'Delete users'],
            ['name' => Permission::USERS_CREATE_COMPANIES, 'slug' => 'users.create_companies', 'description' => 'Create company users only'],
            ['name' => Permission::USERS_CREATE_AGENTS, 'slug' => 'users.create_agents', 'description' => 'Create agent users only'],
            ['name' => Permission::USERS_PASSWORD_RESET, 'slug' => 'users.password_reset', 'description' => 'Reset user passwords'],

            // Candidates
            ['name' => Permission::CANDIDATES_VIEW, 'slug' => 'candidates.view', 'description' => 'View candidates'],
            ['name' => Permission::CANDIDATES_CREATE, 'slug' => 'candidates.create', 'description' => 'Create candidates'],
            ['name' => Permission::CANDIDATES_EDIT, 'slug' => 'candidates.edit', 'description' => 'Edit candidates'],
            ['name' => Permission::CANDIDATES_DELETE, 'slug' => 'candidates.delete', 'description' => 'Delete candidates'],
            ['name' => Permission::CANDIDATES_EXPORT, 'slug' => 'candidates.export', 'description' => 'Export candidates'],

            // Jobs
            ['name' => Permission::JOBS_VIEW, 'slug' => 'jobs.view', 'description' => 'View job posts'],
            ['name' => Permission::JOBS_CREATE, 'slug' => 'jobs.create', 'description' => 'Create job posts'],
            ['name' => Permission::JOBS_EDIT, 'slug' => 'jobs.edit', 'description' => 'Edit job posts'],
            ['name' => Permission::JOBS_DELETE, 'slug' => 'jobs.delete', 'description' => 'Delete job posts'],

            // Finance
            ['name' => Permission::FINANCE_VIEW, 'slug' => 'finances.view', 'description' => 'View finance'],
            ['name' => Permission::FINANCE_CREATE, 'slug' => 'finances.create', 'description' => 'Create finance records'],
            ['name' => Permission::FINANCE_EDIT, 'slug' => 'finances.edit', 'description' => 'Edit finance records'],
            ['name' => Permission::FINANCE_DELETE, 'slug' => 'finances.delete', 'description' => 'Delete finance records'],
            ['name' => Permission::FINANCE_EXPORT, 'slug' => 'finances.export', 'description' => 'Export finance data'],

            // Insurance
            ['name' => Permission::INSURANCE_READ, 'slug' => 'insurance.read', 'description' => 'Read insurance'],
            ['name' => Permission::INSURANCE_CREATE, 'slug' => 'insurance.create', 'description' => 'Create insurance'],
            ['name' => Permission::INSURANCE_UPDATE, 'slug' => 'insurance.update', 'description' => 'Update insurance'],
            ['name' => Permission::INSURANCE_DELETE, 'slug' => 'insurance.delete', 'description' => 'Delete insurance'],

            // Notifications
            ['name' => Permission::NOTIFICATIONS_READ, 'slug' => 'notifications.read', 'description' => 'Read notifications'],
            ['name' => Permission::NOTIFICATIONS_UPDATE, 'slug' => 'notifications.update', 'description' => 'Update notifications'],

            // Agent-Candidate
            ['name' => Permission::AGENT_CANDIDATES_CHANGE_STATUS, 'slug' => 'agent_candidates.change_status', 'description' => 'Change status of agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_DELETE, 'slug' => 'agent_candidates.delete', 'description' => 'Delete agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_VIEW, 'slug' => 'agent_candidates.view', 'description' => 'View agent candidates'],


            // Multi Applicant Generator
            ['name' => Permission::MULTI_APPLICANT_GENERATOR, 'slug' => 'multi_applicant_generator.access', 'description' => 'Access multi applicant generator'],

            // Contracts
            ['name' => Permission::EXPIRED_CONTRACTS_VIEW, 'slug' => 'expired_contracts.view', 'description' => 'View expired contracts'],
            ['name' => Permission::EXPIRED_MEDICAL_INSURANCE_VIEW, 'slug' => 'expired_medical_insurance.view', 'description' => 'View expired medical insurance'],

            // Documents
            ['name' => Permission::DOCUMENTS_VIEW, 'slug' => 'documents.view', 'description' => 'View documents'],
            ['name' => Permission::DOCUMENTS_CREATE, 'slug' => 'documents.create', 'description' => 'Create documents'],
            ['name' => Permission::DOCUMENTS_EDIT, 'slug' => 'documents.edit', 'description' => 'Edit documents'],
            ['name' => Permission::DOCUMENTS_DELETE, 'slug' => 'documents.delete', 'description' => 'Delete documents'],
            ['name' => Permission::DOCUMENTS_UPLOAD, 'slug' => 'documents.upload', 'description' => 'Upload documents'],
            ['name' => Permission::DOCUMENTS_DOWNLOAD, 'slug' => 'documents.download', 'description' => 'Download documents'],
            ['name' => Permission::DOCUMENTS_GENERATE, 'slug' => 'documents.generate', 'description' => 'Generate documents'],
            ['name' => Permission::DOCUMENTS_PREPARATION, 'slug' => 'documents.preparation', 'description' => 'Prepare documents'],

            // Status History
            ['name' => Permission::STATUS_HISTORY_VIEW, 'slug' => 'status_history.view', 'description' => 'View status history'],

            // Job Postings
            ['name' => Permission::JOB_POSTINGS_VIEW, 'slug' => 'job_postings.view', 'description' => 'View job postings'],
            ['name' => Permission::JOB_POSTINGS_CREATE, 'slug' => 'job_postings.create', 'description' => 'Create job postings'],
            ['name' => Permission::JOB_POSTINGS_EDIT, 'slug' => 'job_postings.edit', 'description' => 'Edit job postings'],
            ['name' => Permission::JOB_POSTINGS_DELETE, 'slug' => 'job_postings.delete', 'description' => 'Delete job postings'],

            // Job Positions
            ['name' => Permission::JOB_POSITIONS_VIEW, 'slug' => 'job_positions.view', 'description' => 'View job positions'],
            ['name' => Permission::JOB_POSITIONS_CREATE, 'slug' => 'job_positions.create', 'description' => 'Create job positions'],
            ['name' => Permission::JOB_POSITIONS_EDIT, 'slug' => 'job_positions.edit', 'description' => 'Edit job positions'],
            ['name' => Permission::JOB_POSITIONS_DELETE, 'slug' => 'job_positions.delete', 'description' => 'Delete job positions'],

            //Home
            ['name' => Permission::HOME_VIEW, 'slug' => 'home.view', 'description' => 'View home page'],
            ['name' => Permission::HOME_ARRIVALS, 'slug' => 'home.arrivals', 'description' => 'View home arrivals'],
            ['name' => Permission::HOME_FILTER, 'slug' => 'home.filter', 'description' => 'View home filter'],
            ['name' => Permission::HOME_CHANGE_STATUS, 'slug' => 'home.change_status', 'description' => 'Change status from home page'],

            // Industries
            ['name' => Permission::INDUSTRIES_VIEW, 'slug' => 'industries.view', 'description' => 'View industries'],
            ['name' => Permission::INDUSTRIES_CREATE, 'slug' => 'industries.create', 'description' => 'Create industries'],
            ['name' => Permission::INDUSTRIES_EDIT, 'slug' => 'industries.edit', 'description' => 'Edit industries'],
            ['name' => Permission::INDUSTRIES_DELETE, 'slug' => 'industries.delete', 'description' => 'Delete industries'],


            // Requests
            ['name' => Permission::REQUESTS_VIEW, 'slug' => 'requests.view', 'description' => 'View requests'],
            ['name' => Permission::REQUESTS_APPROVE, 'slug' => 'requests.approve', 'description' => 'Approve requests'],
            ['name' => Permission::REQUESTS_DELETE, 'slug' => 'requests.delete', 'description' => 'Delete requests'],

            // Company Contracts
            ['name' => Permission::COMPANIES_CONTRACTS_CREATE, 'slug' => 'contracts.create', 'description' => 'Create company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_EDIT, 'slug' => 'contracts.edit', 'description' => 'Edit company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_DELETE, 'slug' => 'contracts.delete', 'description' => 'Delete company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_VIEW, 'slug' => 'contracts.view', 'description' => 'View company contracts'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles()
    {
        // Role 1: General Manager - Full access
        $generalManager = Role::find(Role::GENERAL_MANAGER);
        if ($generalManager) {
            $allPermissions = Permission::all()->pluck('id');
            $generalManager->permissions()->sync($allPermissions);
        }

        // Role 2: Manager - Full access except Users (only add companies) & Finance (read-only) + Insurance & Notifications
        $manager = Role::find(Role::MANAGER);
        if ($manager) {
            $managerPermissions = Permission::whereNotIn('name', [
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $manager->permissions()->sync($managerPermissions);
        }

        // Role 3: Company User
        $companyUser = Role::find(Role::COMPANY_USER);
        if ($companyUser) {
            $companyUserPermissions = Permission::whereIn('name', [
                Permission::COMPANIES_VIEW,
                Permission::COMPANIES_CONTRACTS_VIEW,
                Permission::COMPANIES_EDIT,
                Permission::CANDIDATES_VIEW,
                Permission::JOBS_VIEW,
                Permission::JOBS_CREATE,
            ])->pluck('id');

            $companyUser->permissions()->sync($companyUserPermissions);
        }

        // Role 4: Agent - Full access except Companies (no contract register), Users (no access), Finance (no access) + Insurance & Notifications
        $agent = Role::find(Role::AGENT);
        if ($agent) {
            $agentPermissions = Permission::whereIn('name', [
                Permission::JOB_POSTINGS_VIEW,
                Permission::CANDIDATES_VIEW,
            ])->pluck('id');

            $agent->permissions()->sync($agentPermissions);
        }

        // Role 5: Company Owner - Full access except Users (no access), Finance (no access) + Insurance & Notifications
        $companyOwner = Role::find(Role::COMPANY_OWNER);
        if ($companyOwner) {
            $companyOwnerPermissions = Permission::whereIn('name', [
                Permission::COMPANIES_VIEW,
                Permission::COMPANIES_CONTRACTS_VIEW,
                Permission::COMPANIES_EDIT,
                Permission::CANDIDATES_VIEW,
                Permission::JOBS_VIEW,
                Permission::JOBS_CREATE,
            ])->pluck('id');

            $companyOwner->permissions()->sync($companyOwnerPermissions);
        }

        // Role 6: Office - Full access except Companies (no contract register), Users (no access), Job Posts (no access), Finance (no access) + Insurance & Notifications
        $office = Role::find(Role::OFFICE);
        if ($office) {
            $officePermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_EDIT,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_VIEW,
                Permission::USERS_VIEW,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::USERS_CREATE_COMPANIES,
                Permission::USERS_CREATE_AGENTS,
                Permission::JOBS_VIEW,
                Permission::JOBS_CREATE,
                Permission::JOBS_EDIT,
                Permission::JOBS_DELETE,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $office->permissions()->sync($officePermissions);
        }

        // Role 7: HR - Full access except Companies (no contract register), Users (no access), Finance (no access) + Insurance & Notifications
        $hr = Role::find(Role::HR);
        if ($hr) {
            $hrPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_EDIT,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_VIEW,
                Permission::USERS_VIEW,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::USERS_CREATE_COMPANIES,
                Permission::USERS_CREATE_AGENTS,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $hr->permissions()->sync($hrPermissions);
        }

        // Role 8: Office Manager - Full access except Candidates (read-only), Users (only add companies), Finance (no access) + Insurance & Notifications
        $officeManager = Role::find(Role::OFFICE_MANAGER);
        if ($officeManager) {
            $officeManagerPermissions = Permission::whereNotIn('name', [
                Permission::CANDIDATES_CREATE,
                Permission::AGENT_CANDIDATES_VIEW,
                Permission::AGENT_CANDIDATES_CHANGE_STATUS,
                Permission::AGENT_CANDIDATES_DELETE,
                Permission::MULTI_APPLICANT_GENERATOR,
                Permission::CANDIDATES_EDIT,
                Permission::CANDIDATES_DELETE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $officeManager->permissions()->sync($officeManagerPermissions);
        }

        // Role 9: Recruiters - Full access except Companies (no contract register), Users (only add agents), Finance (no access) + Insurance & Notifications
        $recruiters = Role::find(Role::RECRUITERS);
        if ($recruiters) {
            $recruitersPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_EDIT,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_VIEW,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $recruiters->permissions()->sync($recruitersPermissions);
        }

        // Role 10: Finance - Read-only access to Candidates, Companies, Users, Job Posts; Full access to Finance + Insurance & Notifications
        $finance = Role::find(Role::FINANCE);
        if ($finance) {
            $financePermissions = Permission::whereNotIn('name', [
                Permission::CANDIDATES_CREATE,
                Permission::CANDIDATES_EDIT,
                Permission::CANDIDATES_DELETE,
                Permission::COMPANIES_CREATE,
                Permission::COMPANIES_EDIT,
                Permission::COMPANIES_DELETE,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::JOBS_CREATE,
                Permission::JOBS_EDIT,
                Permission::JOBS_DELETE,
            ])->pluck('id');

            $finance->permissions()->sync($financePermissions);
        }
    }
}
