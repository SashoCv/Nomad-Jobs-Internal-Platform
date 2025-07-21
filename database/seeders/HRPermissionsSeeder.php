<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class HRPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions EXCEPT:
        // - Contracts (all contract operations)
        // - Users (all user operations) 
        // - Finances (all finance operations)
        $blockedPermissions = [
            // Contracts - NO ACCESS
            Permission::CONTRACTS_VIEW,
            Permission::CONTRACTS_CREATE,
            Permission::CONTRACTS_EDIT,
            Permission::CONTRACTS_DELETE,
            
            // Users - NO ACCESS
            Permission::USERS_VIEW,
            Permission::USERS_CREATE,
            Permission::USERS_EDIT,
            Permission::USERS_DELETE,
            
            // Finances - NO ACCESS
            Permission::FINANCES_VIEW,
            Permission::FINANCES_CREATE,
            Permission::FINANCES_EDIT,
            Permission::FINANCES_DELETE,
        ];

        $allowedPermissions = Permission::whereNotIn('slug', $blockedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to HR role (role_id = 7)
        $hrRole = Role::find(Role::HR);
        if ($hrRole) {
            $hrRole->permissions()->sync($allowedPermissions);
            
            echo "HR permissions assigned successfully!\n";
            echo "HR has access to " . count($allowedPermissions) . " permissions.\n";
            echo "HR is BLOCKED from:\n";
            echo "- Contracts (all operations)\n";
            echo "- Users (all operations)\n";
            echo "- Finances (all operations)\n";
            echo "HR has full access to other modules including Job Postings.\n";
        } else {
            echo "HR role not found!\n";
        }
    }
}