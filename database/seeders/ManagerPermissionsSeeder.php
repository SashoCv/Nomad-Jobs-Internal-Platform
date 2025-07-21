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
        // Get all permissions except finance create, edit, delete
        $allPermissions = Permission::whereNotIn('slug', [
            Permission::FINANCES_CREATE,
            Permission::FINANCES_EDIT,
            Permission::FINANCES_DELETE
        ])->pluck('id')->toArray();

        // Assign permissions to Manager role (role_id = 2)
        $managerRole = Role::find(Role::MANAGER);
        if ($managerRole) {
            $managerRole->permissions()->sync($allPermissions);
            
            echo "Manager permissions assigned successfully!\n";
            echo "Manager has access to " . count($allPermissions) . " permissions.\n";
            echo "Manager can only VIEW finances, not create/edit/delete.\n";
        } else {
            echo "Manager role not found!\n";
        }
    }
}