<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DryRunCategoryRoleBackfill extends Command
{
    protected $signature = 'categories:backfill-roles-dry-run';
    protected $description = 'Preview which categories are missing category_role pivot entries';

    public function handle(): int
    {
        $categories = DB::table('categories')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('category_role')
                    ->whereColumn('category_role.category_id', 'categories.id');
            })
            ->get(['id', 'candidate_id', 'nameOfCategory']);

        if ($categories->isEmpty()) {
            $this->info('Nothing to fix — all categories already have pivot entries.');
            return 0;
        }

        $this->warn("Found {$categories->count()} categories missing pivot entries:");
        $this->newLine();

        $this->table(
            ['Category ID', 'Candidate ID', 'Name'],
            $categories->map(fn ($c) => [$c->id, $c->candidate_id, $c->nameOfCategory])
        );

        $this->newLine();
        $this->info("These will be given visibility to role 4 (agent) when 'php artisan migrate' runs.");

        return 0;
    }
}
