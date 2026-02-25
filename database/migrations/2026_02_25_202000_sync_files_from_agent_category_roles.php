<?php

use App\Models\Category;
use App\Models\Candidate;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function allRoleIds(): array
    {
        return [
            Role::AGENT,
            Role::GENERAL_MANAGER,
            Role::MANAGER,
            Role::COMPANY_USER,
            Role::COMPANY_OWNER,
            Role::OFFICE,
            Role::HR,
            Role::OFFICE_MANAGER,
            Role::RECRUITERS,
            Role::FINANCE,
        ];
    }

    public function up(): void
    {
        $roleIds = $this->allRoleIds();

        // 1. Sync roles on ALL existing "files from agent" categories
        $existingCategories = Category::where('nameOfCategory', 'files from agent')->get();

        foreach ($existingCategories as $cat) {
            $cat->visibleToRoles()->sync($roleIds);
        }

        // 2. Create missing categories for candidates that don't have one
        $candidatesWithCategory = Category::where('nameOfCategory', 'files from agent')
            ->pluck('candidate_id')
            ->toArray();

        $missingCandidateIds = Candidate::whereNotIn('id', $candidatesWithCategory)
            ->pluck('id');

        foreach ($missingCandidateIds->chunk(500) as $chunk) {
            foreach ($chunk as $candidateId) {
                $cat = Category::create([
                    'candidate_id' => $candidateId,
                    'nameOfCategory' => 'files from agent',
                    'description' => 'Файлове от агент',
                    'isGenerated' => 0,
                ]);

                $cat->visibleToRoles()->sync($roleIds);
            }
        }
    }

    public function down(): void
    {
        // Nothing to reverse - roles were already present before
    }
};
