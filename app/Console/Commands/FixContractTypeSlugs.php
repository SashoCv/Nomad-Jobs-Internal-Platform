<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixContractTypeSlugs extends Command
{
    protected $signature = 'fix:contract-type-slugs
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Convert Cyrillic contract_type values to slugs and fix contract_type_id in company_jobs and candidate_contracts';

    /**
     * Cyrillic name → [slug, contract_type_id]
     */
    private const NAME_TO_SLUG = [
        '90 дни'   => ['slug' => '90days',  'id' => 5],
        '9 месеца' => ['slug' => '9months', 'id' => 4],
        'ЕРПР 1'   => ['slug' => 'erpr1',   'id' => 1],
        'ЕРПР 2'   => ['slug' => 'erpr2',   'id' => 2],
        'ЕРПР 3'   => ['slug' => 'erpr3',   'id' => 3],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Fix Cyrillic contract_type → slugs ===');
        $this->info('');

        $totalFixed = 0;

        $totalFixed += $this->fixTable('company_jobs', 'contract_type', $dryRun);
        $totalFixed += $this->fixTable('candidate_contracts', 'contract_type', $dryRun);

        $this->info('');
        $this->info('=== Summary ===');
        $action = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("{$action}: {$totalFixed} total records");

        return Command::SUCCESS;
    }

    private function fixTable(string $table, string $column, bool $dryRun): int
    {
        $this->info("--- {$table} ---");

        $tableFixed = 0;

        foreach (self::NAME_TO_SLUG as $name => $mapping) {
            $count = DB::table($table)->where($column, $name)->count();

            if ($count === 0) {
                continue;
            }

            $this->line("  '{$name}' → '{$mapping['slug']}' (id={$mapping['id']}): {$count} records");

            if (!$dryRun) {
                DB::table($table)->where($column, $name)->update([
                    $column => $mapping['slug'],
                    'contract_type_id' => $mapping['id'],
                ]);
            }

            $tableFixed += $count;
        }

        if ($tableFixed === 0) {
            $this->info('  All clean - no Cyrillic values found');
        }

        return $tableFixed;
    }
}
