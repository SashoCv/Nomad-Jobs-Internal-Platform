<?php

use App\Models\Category;
use App\Models\Candidate;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get candidate IDs that already have "files from agent" category
        $candidatesWithCategory = Category::where('nameOfCategory', 'files from agent')
            ->pluck('candidate_id')
            ->toArray();

        // Get all candidate IDs that don't have it
        $candidateIds = Candidate::whereNotIn('id', $candidatesWithCategory)
            ->pluck('id');

        $roleIds = [
            Role::AGENT,
            Role::GENERAL_MANAGER,
            Role::MANAGER,
            Role::OFFICE,
            Role::HR,
            Role::OFFICE_MANAGER,
            Role::RECRUITERS,
            Role::FINANCE,
        ];

        $count = 0;

        foreach ($candidateIds->chunk(500) as $chunk) {
            foreach ($chunk as $candidateId) {
                $cat = Category::create([
                    'candidate_id' => $candidateId,
                    'nameOfCategory' => 'files from agent',
                    'description' => 'Файлове от агент',
                    'isGenerated' => 0,
                ]);

                $cat->visibleToRoles()->sync($roleIds);
                $count++;
            }
        }

        DB::statement("SELECT $count as categories_added");
    }

    public function down(): void
    {
        $categoryIds = Category::where('nameOfCategory', 'files from agent')->pluck('id');

        DB::table('category_role')->whereIn('category_id', $categoryIds)->delete();

        Category::where('nameOfCategory', 'files from agent')->delete();
    }
};
