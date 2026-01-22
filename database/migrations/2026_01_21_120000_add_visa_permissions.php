<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Role IDs reference:
        // 1 = General Manager, 2 = Manager, 3 = Company User, 4 = Agent,
        // 5 = Company Owner, 6 = Office, 7 = HR, 8 = Office Manager,
        // 9 = Recruiters, 10 = Finance

        $visaPermissions = [
            [
                'name' => 'Visa Read',
                'slug' => 'visa:read',
                'description' => 'Read visa information',
                'module' => 'visa',
                // All internal roles can read visa info
                'roles' => [1, 2, 6, 7, 8, 9, 10],
            ],
            [
                'name' => 'Visa Create',
                'slug' => 'visa:create',
                'description' => 'Create visa records',
                'module' => 'visa',
                // GM, Manager, Office, HR, Office Manager can create
                'roles' => [1, 2, 6, 7, 8],
            ],
            [
                'name' => 'Visa Update',
                'slug' => 'visa:update',
                'description' => 'Edit visa records',
                'module' => 'visa',
                // GM, Manager, Office, HR, Office Manager can update
                'roles' => [1, 2, 6, 7, 8],
            ],
            [
                'name' => 'Visa Delete',
                'slug' => 'visa:delete',
                'description' => 'Delete visa records',
                'module' => 'visa',
                // Only GM, Manager, Office Manager can delete
                'roles' => [1, 2, 8],
            ],
        ];

        foreach ($visaPermissions as $permission) {
            $roles = $permission['roles'];
            unset($permission['roles']);

            $permission['created_at'] = now();
            $permission['updated_at'] = now();

            $permissionId = DB::table('permissions')->insertGetId($permission);

            foreach ($roles as $roleId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = ['visa:read', 'visa:create', 'visa:update', 'visa:delete'];

        foreach ($slugs as $slug) {
            $permission = DB::table('permissions')->where('slug', $slug)->first();

            if ($permission) {
                DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                DB::table('permissions')->where('id', $permission->id)->delete();
            }
        }
    }
};
