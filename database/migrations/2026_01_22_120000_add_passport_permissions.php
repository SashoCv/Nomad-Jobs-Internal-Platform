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

        $passportPermissions = [
            [
                'name' => 'Passport Read',
                'slug' => 'passport:read',
                'description' => 'Read passport information',
                'module' => 'passport',
                // All internal roles can read passport info
                'roles' => [1, 2, 6, 7, 8, 9, 10],
            ],
            [
                'name' => 'Passport Create',
                'slug' => 'passport:create',
                'description' => 'Create passport records',
                'module' => 'passport',
                // GM, Manager, Office, HR, Office Manager can create
                'roles' => [1, 2, 6, 7, 8],
            ],
            [
                'name' => 'Passport Update',
                'slug' => 'passport:update',
                'description' => 'Edit passport records',
                'module' => 'passport',
                // GM, Manager, Office, HR, Office Manager can update
                'roles' => [1, 2, 6, 7, 8],
            ],
            [
                'name' => 'Passport Delete',
                'slug' => 'passport:delete',
                'description' => 'Delete passport records',
                'module' => 'passport',
                // Only GM, Manager, Office Manager can delete
                'roles' => [1, 2, 8],
            ],
        ];

        foreach ($passportPermissions as $permission) {
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
        $slugs = ['passport:read', 'passport:create', 'passport:update', 'passport:delete'];

        foreach ($slugs as $slug) {
            $permission = DB::table('permissions')->where('slug', $slug)->first();

            if ($permission) {
                DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                DB::table('permissions')->where('id', $permission->id)->delete();
            }
        }
    }
};
