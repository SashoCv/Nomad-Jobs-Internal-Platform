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
        // Allowed to VIEW job postings and specific agent candidate operations
        $allowedPermissions = [
            Permission::JOB_POSTINGS_READ,
            Permission::CANDIDATES_READ,
            Permission::CANDIDATES_FROM_AGENT_CREATE,
            Permission::CANDIDATES_FROM_AGENT_CHANGE_STATUS,
            Permission::CANDIDATES_FROM_AGENT_DELETE,
        ];

        $permissionIds = Permission::whereIn('name', $allowedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Agent role (role_id = 4)
        $agentRole = Role::find(Role::AGENT);
        if ($agentRole) {
            $agentRole->permissions()->sync($permissionIds);
            
            echo "Agent permissions assigned successfully!\n";
            echo "Agent has access to " . count($permissionIds) . " permissions:\n";
            echo "- Job Postings: READ only\n";
            echo "- Candidates: READ only\n";
            echo "- Agent Candidates: CREATE, CHANGE_STATUS, DELETE\n";
            echo "Agent has NO access to other modules (uses existing role-based logic).\n";
        } else {
            echo "Agent role not found!\n";
        }
    }
}