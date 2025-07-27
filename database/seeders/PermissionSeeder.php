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
        $permissions = [
            ['name' => Permission::DASHBOARD_READ, 'slug' => 'dashboard:read', 'description' => 'Read dashboard'],
            ['name' => Permission::COMPANIES_READ, 'slug' => 'companies:read', 'description' => 'Read companies'],
            ['name' => Permission::COMPANIES_CREATE, 'slug' => 'companies:create', 'description' => 'Create companies'],
            ['name' => Permission::COMPANIES_UPDATE, 'slug' => 'companies:update', 'description' => 'Edit companies'],
            ['name' => Permission::COMPANIES_DELETE, 'slug' => 'companies:delete', 'description' => 'Delete companies'],
            ['name' => Permission::COMPANIES_CONTRACTS, 'slug' => 'companies:contracts', 'description' => 'Manage company contracts'],

            ['name' => Permission::USERS_READ, 'slug' => 'users:read', 'description' => 'Read users'],
            ['name' => Permission::USERS_CREATE, 'slug' => 'users:create', 'description' => 'Create users'],
            ['name' => Permission::USERS_UPDATE, 'slug' => 'users:update', 'description' => 'Edit users'],
            ['name' => Permission::USERS_DELETE, 'slug' => 'users:delete', 'description' => 'Delete users'],
            ['name' => Permission::USERS_CREATE_COMPANIES, 'slug' => 'users:create_companies', 'description' => 'Create company users only'],
            ['name' => Permission::USERS_CREATE_AGENTS, 'slug' => 'users:create_agents', 'description' => 'Create agent users only'],
            ['name' => Permission::USERS_PASSWORD_RESET, 'slug' => 'users:password_reset', 'description' => 'Reset user passwords'],

            // Candidates
            ['name' => Permission::CANDIDATES_READ, 'slug' => 'candidates:read', 'description' => 'Read candidates'],
            ['name' => Permission::CANDIDATES_CREATE, 'slug' => 'candidates:create', 'description' => 'Create candidates'],
            ['name' => Permission::CANDIDATES_UPDATE, 'slug' => 'candidates:update', 'description' => 'Edit candidates'],
            ['name' => Permission::CANDIDATES_DELETE, 'slug' => 'candidates:delete', 'description' => 'Delete candidates'],
            ['name' => Permission::CANDIDATES_EXPORT, 'slug' => 'candidates:export', 'description' => 'Export candidates'],

            ['name' => Permission::FINANCE_READ, 'slug' => 'finances:read', 'description' => 'Read finance'],
            ['name' => Permission::FINANCE_CREATE, 'slug' => 'finances:create', 'description' => 'Create finance records'],
            ['name' => Permission::FINANCE_UPDATE, 'slug' => 'finances:update', 'description' => 'Edit finance records'],
            ['name' => Permission::FINANCE_DELETE, 'slug' => 'finances:delete', 'description' => 'Delete finance records'],
            ['name' => Permission::FINANCE_EXPORT, 'slug' => 'finances:export', 'description' => 'Export finance data'],

            ['name' => Permission::INSURANCE_READ, 'slug' => 'insurance:read', 'description' => 'Read insurance'],
            ['name' => Permission::INSURANCE_CREATE, 'slug' => 'insurance:create', 'description' => 'Create insurance'],
            ['name' => Permission::INSURANCE_UPDATE, 'slug' => 'insurance:update', 'description' => 'Update insurance'],
            ['name' => Permission::INSURANCE_DELETE, 'slug' => 'insurance:delete', 'description' => 'Delete insurance'],

            ['name' => Permission::NOTIFICATIONS_READ, 'slug' => 'notifications:read', 'description' => 'Read notifications'],
            ['name' => Permission::NOTIFICATIONS_UPDATE, 'slug' => 'notifications:update', 'description' => 'Update notifications'],

            ['name' => Permission::AGENT_CANDIDATES_CHANGE_STATUS, 'slug' => 'agent_candidates:change_status', 'description' => 'Change status of agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_DELETE, 'slug' => 'agent_candidates:delete', 'description' => 'Delete agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_READ, 'slug' => 'agent_candidates:read', 'description' => 'Read agent candidates'],


            ['name' => Permission::MULTI_APPLICANT_GENERATOR, 'slug' => 'multi_applicant_generator:access', 'description' => 'Access multi applicant generator'],

            ['name' => Permission::EXPIRED_CONTRACTS_READ, 'slug' => 'expired_contracts:read', 'description' => 'Read expired contracts'],
            ['name' => Permission::EXPIRED_MEDICAL_INSURANCE_READ, 'slug' => 'expired_medical_insurance:read', 'description' => 'Read expired medical insurance'],

            ['name' => Permission::DOCUMENTS_READ, 'slug' => 'documents:read', 'description' => 'Read documents'],
            ['name' => Permission::DOCUMENTS_CREATE, 'slug' => 'documents:create', 'description' => 'Create documents'],
            ['name' => Permission::DOCUMENTS_UPDATE, 'slug' => 'documents:update', 'description' => 'Edit documents'],
            ['name' => Permission::DOCUMENTS_DELETE, 'slug' => 'documents:delete', 'description' => 'Delete documents'],
            ['name' => Permission::DOCUMENTS_UPLOAD, 'slug' => 'documents:upload', 'description' => 'Upload documents'],
            ['name' => Permission::DOCUMENTS_DOWNLOAD, 'slug' => 'documents:download', 'description' => 'Download documents'],
            ['name' => Permission::DOCUMENTS_GENERATE, 'slug' => 'documents:generate', 'description' => 'Generate documents'],
            ['name' => Permission::DOCUMENTS_PREPARATION, 'slug' => 'documents:preparation', 'description' => 'Prepare documents'],

            ['name' => Permission::STATUS_HISTORY_READ, 'slug' => 'status_history:read', 'description' => 'Read status history'],

            ['name' => Permission::JOB_POSTINGS_READ, 'slug' => 'job_postings:read', 'description' => 'Read job postings'],
            ['name' => Permission::JOB_POSTINGS_CREATE, 'slug' => 'job_postings:create', 'description' => 'Create job postings'],
            ['name' => Permission::JOB_POSTINGS_UPDATE, 'slug' => 'job_postings:update', 'description' => 'Edit job postings'],
            ['name' => Permission::JOB_POSTINGS_DELETE, 'slug' => 'job_postings:delete', 'description' => 'Delete job postings'],

            ['name' => Permission::JOB_POSITIONS_READ, 'slug' => 'job_positions:read', 'description' => 'Read job positions'],
            ['name' => Permission::JOB_POSITIONS_CREATE, 'slug' => 'job_positions:create', 'description' => 'Create job positions'],
            ['name' => Permission::JOB_POSITIONS_UPDATE, 'slug' => 'job_positions:update', 'description' => 'Edit job positions'],
            ['name' => Permission::JOB_POSITIONS_DELETE, 'slug' => 'job_positions:delete', 'description' => 'Delete job positions'],

            ['name' => Permission::HOME_READ, 'slug' => 'home:read', 'description' => 'Read home page'],
            ['name' => Permission::HOME_ARRIVALS, 'slug' => 'home:arrivals', 'description' => 'View home arrivals'],
            ['name' => Permission::HOME_FILTER, 'slug' => 'home:filter', 'description' => 'View home filter'],
            ['name' => Permission::HOME_CHANGE_STATUS, 'slug' => 'home:change_status', 'description' => 'Change status from home page'],

            ['name' => Permission::INDUSTRIES_READ, 'slug' => 'industries:read', 'description' => 'Read industries'],
            ['name' => Permission::INDUSTRIES_CREATE, 'slug' => 'industries:create', 'description' => 'Create industries'],
            ['name' => Permission::INDUSTRIES_UPDATE, 'slug' => 'industries:update', 'description' => 'Edit industries'],
            ['name' => Permission::INDUSTRIES_DELETE, 'slug' => 'industries:delete', 'description' => 'Delete industries'],

            ['name' => Permission::COMPANY_JOB_REQUESTS_READ, 'slug' => 'requests:read', 'description' => 'Read company job requests'],
            ['name' => Permission::COMPANY_JOB_REQUESTS_APPROVE, 'slug' => 'requests:approve', 'description' => 'Approve company job requests'],
            ['name' => Permission::COMPANY_JOB_REQUESTS_DELETE, 'slug' => 'requests:delete', 'description' => 'Delete company job requests'],

            ['name' => Permission::COMPANIES_CONTRACTS_CREATE, 'slug' => 'companies_contracts:create', 'description' => 'Create company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_UPDATE, 'slug' => 'companies_contracts:update', 'description' => 'Edit company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_DELETE, 'slug' => 'companies_contracts:delete', 'description' => 'Delete company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_READ, 'slug' => 'companies_contracts:read', 'description' => 'Read company contracts'],

            ['name' => Permission::CHANGE_LOGS_READ, 'slug' => 'change_logs:read', 'description' => 'Read change logs'],
            ['name' => Permission::CHANGE_LOGS_CREATE, 'slug' => 'change_logs:create', 'description' => 'Create change logs'],
            ['name' => Permission::CHANGE_LOGS_APPROVE, 'slug' => 'change_logs:approve', 'description' => 'Approve change logs'],
            ['name' => Permission::CHANGE_LOGS_DELETE, 'slug' => 'change_logs:delete', 'description' => 'Delete change logs'],
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
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_UPDATE,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $manager->permissions()->sync($managerPermissions);
        }

        // Role 3: Company User
        $companyUser = Role::find(Role::COMPANY_USER);
        if ($companyUser) {
            $companyUserPermissions = Permission::whereIn('name', [
                Permission::COMPANIES_READ,
                Permission::COMPANIES_CONTRACTS_READ,
                Permission::COMPANIES_UPDATE,
                Permission::CANDIDATES_READ,
                Permission::JOB_POSTINGS_READ,
                Permission::JOB_POSTINGS_CREATE,
                Permission::COMPANY_JOB_REQUESTS_READ,
                Permission::CHANGE_LOGS_READ,
            ])->pluck('id');

            $companyUser->permissions()->sync($companyUserPermissions);
        }

        // Role 4: Agent - Full access except Companies (no contract register), Users (no access), Finance (no access) + Insurance & Notifications
        $agent = Role::find(Role::AGENT);
        if ($agent) {
            $agentPermissions = Permission::whereIn('name', [
                Permission::JOB_POSTINGS_READ,
                Permission::CANDIDATES_READ,
            ])->pluck('id');

            $agent->permissions()->sync($agentPermissions);
        }

        // Role 5: Company Owner - Full access except Users (no access), Finance (no access) + Insurance & Notifications
        $companyOwner = Role::find(Role::COMPANY_OWNER);
        if ($companyOwner) {
            $companyOwnerPermissions = Permission::whereIn('name', [
                Permission::COMPANIES_READ,
                Permission::COMPANIES_CONTRACTS_READ,
                Permission::COMPANIES_UPDATE,
                Permission::CANDIDATES_READ,
                Permission::JOB_POSTINGS_READ,
                Permission::JOB_POSTINGS_CREATE,
                Permission::COMPANY_JOB_REQUESTS_READ,
                Permission::CHANGE_LOGS_READ,
            ])->pluck('id');

            $companyOwner->permissions()->sync($companyOwnerPermissions);
        }

        // Role 6: Office - Full access except Companies (no contract register), Users (no access), Job Posts (no access), Finance (no access) + Insurance & Notifications
        $office = Role::find(Role::OFFICE);
        if ($office) {
            $officePermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_UPDATE,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_READ,
                Permission::USERS_READ,
                Permission::USERS_CREATE,
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
                Permission::USERS_CREATE_COMPANIES,
                Permission::USERS_CREATE_AGENTS,
                Permission::FINANCE_READ,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_UPDATE,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $office->permissions()->sync($officePermissions);
        }

        // Role 7: HR - Full access except Companies (no contract register), Users (no access), Finance (no access) + Insurance & Notifications
        $hr = Role::find(Role::HR);
        if ($hr) {
            $hrPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_UPDATE,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_READ,
                Permission::USERS_READ,
                Permission::USERS_CREATE,
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
                Permission::USERS_CREATE_COMPANIES,
                Permission::USERS_CREATE_AGENTS,
                Permission::FINANCE_READ,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_UPDATE,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $hr->permissions()->sync($hrPermissions);
        }

        // Role 8: Office Manager - Full access except Candidates (read-only), Users (only add companies), Finance (no access) + Insurance & Notifications
        $officeManager = Role::find(Role::OFFICE_MANAGER);
        if ($officeManager) {
            $officeManagerPermissions = Permission::whereNotIn('name', [
                Permission::CANDIDATES_CREATE,
                Permission::AGENT_CANDIDATES_READ,
                Permission::AGENT_CANDIDATES_CHANGE_STATUS,
                Permission::AGENT_CANDIDATES_DELETE,
                Permission::MULTI_APPLICANT_GENERATOR,
                Permission::CANDIDATES_UPDATE,
                Permission::CANDIDATES_DELETE,
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
                Permission::FINANCE_READ,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_UPDATE,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $officeManager->permissions()->sync($officeManagerPermissions);
        }

        // Role 9: Recruiters - Full access except Companies (no contract register), Users (only add agents), Finance (no access) + Insurance & Notifications
        $recruiters = Role::find(Role::RECRUITERS);
        if ($recruiters) {
            $recruitersPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS_CREATE,
                Permission::COMPANIES_CONTRACTS_UPDATE,
                Permission::COMPANIES_CONTRACTS_DELETE,
                Permission::COMPANIES_CONTRACTS_READ,
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
                Permission::FINANCE_READ,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_UPDATE,
                Permission::FINANCE_DELETE
            ])->pluck('id');

            $recruiters->permissions()->sync($recruitersPermissions);
        }

        // Role 10: Finance - Read-only access to Candidates, Companies, Users, Job Posts; Full access to Finance + Insurance & Notifications
        $finance = Role::find(Role::FINANCE);
        if ($finance) {
            $financePermissions = Permission::whereNotIn('name', [
                Permission::CANDIDATES_CREATE,
                Permission::CANDIDATES_UPDATE,
                Permission::CANDIDATES_DELETE,
                Permission::COMPANIES_CREATE,
                Permission::COMPANIES_UPDATE,
                Permission::COMPANIES_DELETE,
                Permission::USERS_CREATE,
                Permission::USERS_UPDATE,
                Permission::USERS_DELETE,
            ])->pluck('id');

            $finance->permissions()->sync($financePermissions);
        }
    }
}
