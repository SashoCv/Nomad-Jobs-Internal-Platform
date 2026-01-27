<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_role', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'role_id']);
        });

        DB::transaction(function () {
            $categories = DB::table('categories')->whereNotNull('allowed_roles')->get();

            foreach ($categories as $category) {
                $allowedRoles = json_decode($category->allowed_roles, true);

                if (is_array($allowedRoles)) {
                    foreach ($allowedRoles as $roleId) {
                        $roleId = (int) $roleId;

                        $roleExists = DB::table('roles')->where('id', $roleId)->exists();
                        if ($roleExists) {
                            DB::table('category_role')->insertOrIgnore([
                                'category_id' => $category->id,
                                'role_id' => $roleId,
                            ]);
                        }
                    }
                } else {
                    Log::warning("Invalid allowed_roles JSON for category {$category->id}");
                }

                if ($category->role_id) {
                    $roleExists = DB::table('roles')->where('id', $category->role_id)->exists();
                    if ($roleExists) {
                        DB::table('category_role')->insertOrIgnore([
                            'category_id' => $category->id,
                            'role_id' => $category->role_id,
                        ]);
                    }
                }
            }

            $categoriesWithOnlyRoleId = DB::table('categories')
                ->whereNull('allowed_roles')
                ->whereNotNull('role_id')
                ->get();

            foreach ($categoriesWithOnlyRoleId as $category) {
                $roleExists = DB::table('roles')->where('id', $category->role_id)->exists();
                if ($roleExists) {
                    DB::table('category_role')->insertOrIgnore([
                        'category_id' => $category->id,
                        'role_id' => $category->role_id,
                    ]);
                }
            }
        });
    }
};
