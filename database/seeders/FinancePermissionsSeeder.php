<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class FinancePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions EXCEPT:
        // - Candidates create, edit, delete (only view)
        // - Companies create, edit, delete (only view)
        // - Users create, create_companies, create_agents, edit, delete (only view)
        // - Job Postings create, edit, delete (only view)
        $blockedPermissions = [
            // Candidates - only VIEW allowed (block create, edit, delete)
            Permission::CANDIDATES_CREATE,
            Permission::CANDIDATES_EDIT,
            Permission::CANDIDATES_DELETE,
            
            // Companies - only VIEW allowed (block create, edit, delete)
            Permission::COMPANIES_CREATE,
            Permission::COMPANIES_EDIT,
            Permission::COMPANIES_DELETE,
            
            // Users - only VIEW allowed (block all create/edit/delete operations)
            Permission::USERS_CREATE,
            Permission::USERS_CREATE_COMPANIES,
            Permission::USERS_CREATE_AGENTS,
            Permission::USERS_EDIT,
            Permission::USERS_DELETE,
            
            // Job Postings - only VIEW allowed (block create, edit, delete)
            Permission::JOB_POSTINGS_CREATE,
            Permission::JOB_POSTINGS_EDIT,
            Permission::JOB_POSTINGS_DELETE,
        ];

        $allowedPermissions = Permission::whereNotIn('slug', $blockedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Finance role (role_id = 10)
        $financeRole = Role::find(Role::FINANCE);
        if ($financeRole) {
            $financeRole->permissions()->sync($allowedPermissions);
            
            echo "Finance permissions assigned successfully!\n";
            echo "Finance has access to " . count($allowedPermissions) . " permissions.\n";
            echo "Finance restrictions (VIEW ONLY):\n";
            echo "- Candidates: ONLY VIEW (no create/edit/delete)\n";
            echo "- Companies: ONLY VIEW (no create/edit/delete)\n";
            echo "- Users: ONLY VIEW (no create/edit/delete)\n";
            echo "- Job Postings: ONLY VIEW (no create/edit/delete)\n";
            echo "Finance has FULL access to all other modules including Finances.\n";
        } else {
            echo "Finance role not found!\n";
        }
    }
}