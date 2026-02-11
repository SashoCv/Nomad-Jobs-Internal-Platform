<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $orphanedCategoryIds = DB::table('categories')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('category_role')
                    ->whereColumn('category_role.category_id', 'categories.id');
            })
            ->pluck('id');

        if ($orphanedCategoryIds->isEmpty()) {
            return;
        }

        $agentRoleId = 4;

        $rows = $orphanedCategoryIds->map(fn ($id) => [
            'category_id' => $id,
            'role_id'     => $agentRoleId,
        ])->toArray();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('category_role')->insert($chunk);
        }
    }

    public function down(): void
    {
        // No rollback — these pivot entries should persist
    }
};
