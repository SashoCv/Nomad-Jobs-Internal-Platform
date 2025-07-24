<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class ManagerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions except:
        // - User permissions (except create companies)
        // - Finance create, edit, delete (only view)
        $excludedPermissions = [
            // User restrictions - can only create companies
            Permission::USERS_CREATE,
            Permission::USERS_CREATE_AGENTS,
            Permission::USERS_EDIT,
            Permission::USERS_DELETE,
            // Finance restrictions - view only
            Permission::FINANCES_CREATE,
            Permission::FINANCES_EDIT,
            Permission::FINANCES_DELETE,
            Permission::FINANCE_CREATE,
            Permission::FINANCE_EDIT,
            Permission::FINANCE_DELETE,
        ];

        $allPermissions = Permission::whereNotIn('slug', $excludedPermissions)->pluck('id')->toArray();

        // Add specific permission to create companies (clients)
        $createCompaniesPermission = Permission::where('slug', Permission::USERS_CREATE_COMPANIES)->first();
        if ($createCompaniesPermission) {
            $allPermissions[] = $createCompaniesPermission->id;
        }

        // Assign permissions to Manager role (role_id = 2)
        $managerRole = Role::find(Role::MANAGER);
        if ($managerRole) {
            $managerRole->permissions()->sync($allPermissions);
            
            echo "Manager permissions assigned successfully!\n";
            echo "Manager has access to " . count($allPermissions) . " permissions.\n";
            echo "Manager restrictions:\n";
            echo "- Users: Can only add companies (clients)\n";
            echo "- Finance: View-only access\n";
        } else {
            echo "Manager role not found!\n";
        }
    }
}