<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the impersonate permission
        DB::table('permissions')->insertOrIgnore([
            'name' => Permission::USERS_IMPERSONATE,
            'slug' => 'users:impersonate',
            'description' => 'Impersonate other users (login as user)',
            'module' => 'users',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get the permission ID
        $permission = DB::table('permissions')->where('slug', 'users:impersonate')->first();

        if ($permission) {
            // Assign to GENERAL_MANAGER role (role_id = 1)
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => Role::GENERAL_MANAGER,
                'permission_id' => $permission->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Get the permission
        $permission = DB::table('permissions')->where('slug', 'users:impersonate')->first();

        if ($permission) {
            // Remove from role_permissions
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
        }

        // Remove the permission
        DB::table('permissions')->where('slug', 'users:impersonate')->delete();
    }
};
