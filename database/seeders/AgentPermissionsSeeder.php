<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AgentPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Agent permissions - limited access
        // Allowed to VIEW job postings and full CRUD for candidates
        $allowedPermissions = [
            Permission::JOB_POSTINGS_VIEW,
            Permission::CANDIDATES_VIEW,
            Permission::CANDIDATES_CREATE,
            Permission::CANDIDATES_EDIT,
            Permission::CANDIDATES_DELETE,
        ];

        $permissionIds = Permission::whereIn('slug', $allowedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Agent role (role_id = 4)
        $agentRole = Role::find(Role::AGENT);
        if ($agentRole) {
            $agentRole->permissions()->sync($permissionIds);
            
            echo "Agent permissions assigned successfully!\n";
            echo "Agent has access to " . count($permissionIds) . " permissions:\n";
            echo "- Job Postings: VIEW only\n";
            echo "- Candidates: FULL ACCESS (view, create, edit, delete)\n";
            echo "Agent has NO access to other modules (uses existing role-based logic).\n";
        } else {
            echo "Agent role not found!\n";
        }
    }
}