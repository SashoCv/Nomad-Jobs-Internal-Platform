<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\Permissions\AgentPermissions;

class AgentPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allowedPermissions = AgentPermissions::getPermissions();

        $permissionIds = Permission::whereIn('slug', $allowedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Agent role (role_id = 4)
        $agentRole = Role::find(Role::AGENT);
        if ($agentRole) {
            $agentRole->permissions()->sync($permissionIds);

            echo "Agent permissions assigned successfully!\n";
            echo "Agent has access to " . count($permissionIds) . " permissions:\n";
            foreach ($allowedPermissions as $perm) {
                echo "- $perm\n";
            }
        } else {
            echo "Agent role not found!\n";
        }
    }
}
