<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class OfficeManagerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, add the new company user creation permission if it doesn't exist
        Permission::firstOrCreate([
            'slug' => Permission::USERS_CREATE_COMPANIES
        ], [
            'name' => 'Users Create Companies Only',
            'slug' => Permission::USERS_CREATE_COMPANIES,
            'description' => 'Can create only company (client) users',
            'module' => 'users'
        ]);

        // Get all permissions EXCEPT:
        // - Candidates create, edit, delete (only view)
        // - Users create, edit, delete (only view + create companies)
        // - Finances (all operations)
        $blockedPermissions = [
            // Candidates - only VIEW allowed (block create, edit, delete)
            Permission::CANDIDATES_CREATE,
            Permission::CANDIDATES_EDIT,
            Permission::CANDIDATES_DELETE,
            
            // Users - only VIEW + CREATE_COMPANIES allowed (block general create, edit, delete)
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

        // Assign permissions to Office Manager role (role_id = 8)
        $officeManagerRole = Role::find(Role::OFFICE_MANAGER);
        if ($officeManagerRole) {
            $officeManagerRole->permissions()->sync($allowedPermissions);
            
            echo "Office Manager permissions assigned successfully!\n";
            echo "Office Manager has access to " . count($allowedPermissions) . " permissions.\n";
            echo "Office Manager restrictions:\n";
            echo "- Candidates: ONLY VIEW (no create/edit/delete)\n";
            echo "- Users: Can VIEW + CREATE COMPANIES only (no general create/edit/delete)\n";
            echo "- Finances: NO ACCESS\n";
            echo "Office Manager has full access to other modules.\n";
        } else {
            echo "Office Manager role not found!\n";
        }
    }
}