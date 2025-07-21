<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class OfficePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions EXCEPT:
        // - Contracts (all contract operations)
        // - Users (all user operations) 
        // - Job Postings (all job posting operations)
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
            
            // Job Postings - NO ACCESS
            Permission::JOB_POSTINGS_VIEW,
            Permission::JOB_POSTINGS_CREATE,
            Permission::JOB_POSTINGS_EDIT,
            Permission::JOB_POSTINGS_DELETE,
            
            // Finances - NO ACCESS
            Permission::FINANCES_VIEW,
            Permission::FINANCES_CREATE,
            Permission::FINANCES_EDIT,
            Permission::FINANCES_DELETE,
        ];

        $allowedPermissions = Permission::whereNotIn('slug', $blockedPermissions)
            ->pluck('id')->toArray();

        // Assign permissions to Office role (role_id = 6)
        $officeRole = Role::find(Role::OFFICE);
        if ($officeRole) {
            $officeRole->permissions()->sync($allowedPermissions);
            
            echo "Office permissions assigned successfully!\n";
            echo "Office has access to " . count($allowedPermissions) . " permissions.\n";
            echo "Office is BLOCKED from:\n";
            echo "- Contracts (all operations)\n";
            echo "- Users (all operations)\n";
            echo "- Job Postings (all operations)\n";
            echo "- Finances (all operations)\n";
            echo "Office has full access to other modules.\n";
        } else {
            echo "Office role not found!\n";
        }
    }
}