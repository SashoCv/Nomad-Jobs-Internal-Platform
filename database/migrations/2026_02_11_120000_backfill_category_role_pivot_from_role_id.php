<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultCategoryNames = ['За пристигане', 'За виза'];
        $agentRoleId = 4;
        $staffRoleIds = [1, 2, 6, 7, 8, 9, 10];

        // Default agent categories → visible to agents
        $agentCategoryIds = DB::table('categories')
            ->whereIn('nameOfCategory', $defaultCategoryNames)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('category_role')
                    ->whereColumn('category_role.category_id', 'categories.id');
            })
            ->pluck('id');

        $agentRows = $agentCategoryIds->map(fn ($id) => [
            'category_id' => $id,
            'role_id'     => $agentRoleId,
        ])->toArray();

        // Other orphaned categories → visible to all staff roles
        $staffCategoryIds = DB::table('categories')
            ->whereNotIn('nameOfCategory', $defaultCategoryNames)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('category_role')
                    ->whereColumn('category_role.category_id', 'categories.id');
            })
            ->pluck('id');

        $staffRows = [];
        foreach ($staffCategoryIds as $catId) {
            foreach ($staffRoleIds as $roleId) {
                $staffRows[] = [
                    'category_id' => $catId,
                    'role_id'     => $roleId,
                ];
            }
        }

        $allRows = array_merge($agentRows, $staffRows);

        foreach (array_chunk($allRows, 500) as $chunk) {
            DB::table('category_role')->insert($chunk);
        }
    }

    public function down(): void
    {
        // No rollback — these pivot entries should persist
    }
};
