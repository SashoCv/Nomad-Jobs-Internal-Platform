<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new permissions
        $permissions = [
            [
                'name' => Permission::APPROVED_CANDIDATES_READ,
                'slug' => 'approved_candidates:read',
                'description' => 'Access approved candidates page',
                'module' => 'home'
            ],
            [
                'name' => Permission::HR_REPORTS_READ,
                'slug' => 'hr_reports:read',
                'description' => 'Access HR reports page',
                'module' => 'home'
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'description' => $permission['description'],
                'module' => $permission['module'],
                'created_at' => now(),
                'updated_at' => now(),
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
        // Remove the permissions
        DB::table('permissions')->whereIn('slug', [
            'approved_candidates:read',
            'hr_reports:read',
        ])->delete();
    }
};
