<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::find(Role::AGENT);
        if (!$role) {
            return;
        }

        $permissionSlugs = [
            'home:read',
            'home:arrivals',
        ];

        $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->toArray();

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $role = Role::find(Role::AGENT);
        if (!$role) {
            return;
        }

        $permissionSlugs = [
            'home:read',
            'home:arrivals',
        ];

        $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->toArray();

        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
