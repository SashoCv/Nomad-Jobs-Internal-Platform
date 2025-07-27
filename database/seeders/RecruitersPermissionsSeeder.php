<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RecruitersPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, add the new agent creation permission if it doesn't exist
        Permission::firstOrCreate([
            'slug' => Permission::USERS_CREATE_AGENTS
        ], [
            'name' => 'Users Create Agents Only',
            'slug' => Permission::USERS_CREATE_AGENTS,
            'description' => 'Can create only agent users',
            'module' => 'users'
        ]);

        // Get all permissions EXCEPT:
        // - Contracts (all contract operations)
        // - Users create, edit, delete (only view + create agents)
        // - Finances (all operations)
        $blockedPermissions = [
            // Contracts - NO ACCESS
            Permission::CONTRACTS_VIEW,
            Permission::CONTRACTS_CREATE,
            Permission::CONTRACTS_EDIT,
            Permission::CONTRACTS_DELETE,
            
            // Users - only VIEW + CREATE_AGENTS allowed (block general create, companies create, edit, delete)
            Permission::USERS_CREATE,
            Permission::USERS_CREATE_COMPANIES,
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

        // Assign permissions to Recruiters role (role_id = 9)
        $recruitersRole = Role::find(Role::RECRUITERS);
        if ($recruitersRole) {
            $recruitersRole->permissions()->sync($allowedPermissions);
            
            echo "Recruiters permissions assigned successfully!\n";
            echo "Recruiters has access to " . count($allowedPermissions) . " permissions.\n";
            echo "Recruiters restrictions:\n";
            echo "- Companies: NO access to Contracts\n";
            echo "- Users: Can VIEW + CREATE AGENTS only (no companies/general create/edit/delete)\n";
            echo "- Finances: NO ACCESS\n";
            echo "Recruiters has full access to other modules including Candidates and Job Postings.\n";
        } else {
            echo "Recruiters role not found!\n";
        }
    }
}