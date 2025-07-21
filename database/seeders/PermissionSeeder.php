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
            // Companies
            ['name' => Permission::COMPANIES_VIEW, 'description' => 'View companies'],
            ['name' => Permission::COMPANIES_CREATE, 'description' => 'Create companies'],
            ['name' => Permission::COMPANIES_EDIT, 'description' => 'Edit companies'],
            ['name' => Permission::COMPANIES_DELETE, 'description' => 'Delete companies'],
            ['name' => Permission::COMPANIES_CONTRACTS, 'description' => 'Manage company contracts'],

            // Users
            ['name' => Permission::USERS_VIEW, 'description' => 'View users'],
            ['name' => Permission::USERS_CREATE, 'description' => 'Create users'],
            ['name' => Permission::USERS_EDIT, 'description' => 'Edit users'],
            ['name' => Permission::USERS_DELETE, 'description' => 'Delete users'],
            ['name' => Permission::USERS_CREATE_COMPANIES, 'description' => 'Create company users only'],
            ['name' => Permission::USERS_CREATE_AGENTS, 'description' => 'Create agent users only'],

            // Candidates
            ['name' => Permission::CANDIDATES_VIEW, 'description' => 'View candidates'],
            ['name' => Permission::CANDIDATES_CREATE, 'description' => 'Create candidates'],
            ['name' => Permission::CANDIDATES_EDIT, 'description' => 'Edit candidates'],
            ['name' => Permission::CANDIDATES_DELETE, 'description' => 'Delete candidates'],

            // Jobs
            ['name' => Permission::JOBS_VIEW, 'description' => 'View job posts'],
            ['name' => Permission::JOBS_CREATE, 'description' => 'Create job posts'],
            ['name' => Permission::JOBS_EDIT, 'description' => 'Edit job posts'],
            ['name' => Permission::JOBS_DELETE, 'description' => 'Delete job posts'],

            // Finance
            ['name' => Permission::FINANCE_VIEW, 'description' => 'View finance'],
            ['name' => Permission::FINANCE_CREATE, 'description' => 'Create finance records'],
            ['name' => Permission::FINANCE_EDIT, 'description' => 'Edit finance records'],
            ['name' => Permission::FINANCE_DELETE, 'description' => 'Delete finance records'],
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

        // Role 2: Manager - Full access except Users (only add companies) & Finance (read-only)
        $manager = Role::find(Role::MANAGER);
        if ($manager) {
            $managerPermissions = Permission::whereNotIn('name', [
                Permission::USERS_VIEW,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');
            
            $additionalPermissions = Permission::whereIn('name', [
                Permission::USERS_CREATE_COMPANIES,
                Permission::FINANCE_VIEW
            ])->pluck('id');
            
            $manager->permissions()->sync($managerPermissions->merge($additionalPermissions));
        }

        // Role 6: Office - Full access except Companies (no contract register), Users (no access), Job Posts (no access), Finance (no access)
        $office = Role::find(Role::OFFICE);
        if ($office) {
            $officePermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS,
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

        // Role 7: HR - Full access except Companies (no contract register), Users (no access), Finance (no access)
        $hr = Role::find(Role::HR);
        if ($hr) {
            $hrPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS,
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

        // Role 8: Office Manager - Full access except Candidates (read-only), Users (only add companies), Finance (no access)
        $officeManager = Role::find(Role::OFFICE_MANAGER);
        if ($officeManager) {
            $officeManagerPermissions = Permission::whereNotIn('name', [
                Permission::CANDIDATES_CREATE,
                Permission::CANDIDATES_EDIT,
                Permission::CANDIDATES_DELETE,
                Permission::USERS_VIEW,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');
            
            $additionalPermissions = Permission::whereIn('name', [
                Permission::CANDIDATES_VIEW,
                Permission::USERS_CREATE_COMPANIES
            ])->pluck('id');
            
            $officeManager->permissions()->sync($officeManagerPermissions->merge($additionalPermissions));
        }

        // Role 9: Recruiters - Full access except Companies (no contract register), Users (only add agents), Finance (no access)
        $recruiters = Role::find(Role::RECRUITERS);
        if ($recruiters) {
            $recruitersPermissions = Permission::whereNotIn('name', [
                Permission::COMPANIES_CONTRACTS,
                Permission::USERS_VIEW,
                Permission::USERS_CREATE,
                Permission::USERS_EDIT,
                Permission::USERS_DELETE,
                Permission::USERS_CREATE_COMPANIES,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');
            
            $additionalPermissions = Permission::whereIn('name', [
                Permission::USERS_CREATE_AGENTS
            ])->pluck('id');
            
            $recruiters->permissions()->sync($recruitersPermissions->merge($additionalPermissions));
        }

        // Role 10: Finance - Read-only access to Candidates, Companies, Users, Job Posts; Full access to Finance
        $finance = Role::find(Role::FINANCE);
        if ($finance) {
            $financePermissions = Permission::whereIn('name', [
                Permission::CANDIDATES_VIEW,
                Permission::COMPANIES_VIEW,
                Permission::USERS_VIEW,
                Permission::JOBS_VIEW,
                Permission::FINANCE_VIEW,
                Permission::FINANCE_CREATE,
                Permission::FINANCE_EDIT,
                Permission::FINANCE_DELETE
            ])->pluck('id');
            
            $finance->permissions()->sync($financePermissions);
        }
    }
}
