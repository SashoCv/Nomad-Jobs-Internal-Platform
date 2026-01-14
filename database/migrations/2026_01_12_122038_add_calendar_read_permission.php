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
        // Add the calendar:read permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'Calendar Read',
            'slug' => 'calendar:read',
            'description' => 'View calendar events',
            'module' => 'calendar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign to roles (excluding Company User=3, Agent=4, Company Owner=5)
        $roleIds = [1, 2, 6, 7, 8, 9, 10]; // GM, Manager, Office, HR, Office Manager, Recruiters, Finance

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'calendar:read')->first();

        if ($permission) {
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
