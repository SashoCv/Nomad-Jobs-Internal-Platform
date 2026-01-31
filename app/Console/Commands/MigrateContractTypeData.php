<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateContractTypeData extends Command
{
    protected $signature = 'contracts:migrate-types {--dry-run : Show what would be done without making changes}';

    protected $description = 'Migrate contract type string values to contract_type_id foreign key';

    // Default contract type for empty/null values
    private const DEFAULT_CONTRACT_TYPE_SLUG = 'erpr1';

    /**
     * Map various string formats to contract type slugs
     */
    private function normalizeSlug(?string $value): ?string
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // Direct slug matches (lowercase)
        $directSlugs = ['erpr1', 'erpr2', 'erpr3', '90days', '9months'];
        if (in_array(strtolower($value), $directSlugs)) {
            return strtolower($value);
        }

        // Map display names and legacy values to slugs
        $mapping = [
            // ERPR 1
            'ЕРПР 1' => 'erpr1',
            'ERPR 1' => 'erpr1',

            // ERPR 2
            'ЕРПР 2' => 'erpr2',
            'ERPR 2' => 'erpr2',

            // ERPR 3
            'ЕРПР 3' => 'erpr3',
            'ERPR 3' => 'erpr3',
            'indefinite' => 'erpr3',  // Legacy: indefinite maps to ERPR 3
            'безсрочен' => 'erpr3',

            // 90 days
            '90 дни' => '90days',
            '90 days' => '90days',

            // 9 months
            '9 месеца' => '9months',
            '9 months' => '9months',
        ];

        return $mapping[$value] ?? null;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('');
        $this->info('=== Migrating Contract Type Data ===');
        $this->info('');

        // Load contract types into memory (slug => id mapping)
        $contractTypes = DB::table('contract_types')->pluck('id', 'slug')->toArray();
        $this->info('Loaded ' . count($contractTypes) . ' contract types');
        foreach ($contractTypes as $slug => $id) {
            $this->line("  {$slug} => ID {$id}");
        }
        $this->info('');

        // Migrate company_jobs
        $this->migrateTable('company_jobs', 'contract_type', $contractTypes, $dryRun);

        // Migrate candidates (uses contractType column name)
        $this->migrateTable('candidates', 'contractType', $contractTypes, $dryRun);

        // Migrate candidate_contracts
        $this->migrateTable('candidate_contracts', 'contract_type', $contractTypes, $dryRun);

        // Fix records with empty/null contract_type but no contract_type_id
        $this->fixEmptyContractTypes($contractTypes, $dryRun);

        $this->info('');
        $this->info('=== Migration Complete ===');

        return Command::SUCCESS;
    }

    private function migrateTable(string $table, string $column, array $contractTypes, bool $dryRun): void
    {
        $this->info("Migrating {$table}.{$column}...");

        // Get distinct values that need mapping
        $values = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->whereNull('contract_type_id')
            ->distinct()
            ->pluck($column);

        $this->line("  Found {$values->count()} distinct values to map");

        $totalUpdated = 0;
        $unmapped = [];

        foreach ($values as $value) {
            $slug = $this->normalizeSlug($value);

            if (!$slug || !isset($contractTypes[$slug])) {
                $unmapped[] = $value;
                continue;
            }

            $typeId = $contractTypes[$slug];
            $typeName = $this->getContractTypeName($slug);

            // Count records to update
            $count = DB::table($table)
                ->where($column, $value)
                ->whereNull('contract_type_id')
                ->count();

            if ($count > 0) {
                $this->line("    '{$value}' → {$slug} (ID: {$typeId}) - {$count} records");

                if (!$dryRun) {
                    DB::table($table)
                        ->where($column, $value)
                        ->whereNull('contract_type_id')
                        ->update([
                            'contract_type_id' => $typeId,
                            $column => $typeName, // Also normalize the string value
                        ]);
                }

                $totalUpdated += $count;
            }
        }

        $action = $dryRun ? 'Would update' : 'Updated';
        $this->info("  {$action} {$totalUpdated} records in {$table}");

        if (!empty($unmapped)) {
            $this->warn("  Unmapped values: " . implode(', ', array_map(fn($v) => "'{$v}'", $unmapped)));
        }

        $this->info('');
    }

    /**
     * Fix records that have empty/null contract_type and no contract_type_id
     */
    private function fixEmptyContractTypes(array $contractTypes, bool $dryRun): void
    {
        $this->info('Fixing records with empty contract_type...');

        $defaultSlug = self::DEFAULT_CONTRACT_TYPE_SLUG;
        $defaultId = $contractTypes[$defaultSlug] ?? null;
        $defaultName = $this->getContractTypeName($defaultSlug);

        if (!$defaultId) {
            $this->error("  Default contract type '{$defaultSlug}' not found!");
            return;
        }

        $tables = [
            'company_jobs' => 'contract_type',
            'candidates' => 'contractType',
            'candidate_contracts' => 'contract_type',
        ];

        foreach ($tables as $table => $column) {
            $count = DB::table($table)
                ->whereNull('contract_type_id')
                ->where(function ($query) use ($column) {
                    $query->whereNull($column)
                        ->orWhere($column, '');
                })
                ->count();

            if ($count > 0) {
                $this->line("  {$table}: {$count} records with empty contract_type → {$defaultSlug} (ID: {$defaultId})");

                if (!$dryRun) {
                    DB::table($table)
                        ->whereNull('contract_type_id')
                        ->where(function ($query) use ($column) {
                            $query->whereNull($column)
                                ->orWhere($column, '');
                        })
                        ->update([
                            'contract_type_id' => $defaultId,
                            $column => $defaultName,
                        ]);
                }
            } else {
                $this->line("  {$table}: No records with empty contract_type");
            }
        }

        $this->info('');
    }

    /**
     * Get the display name for a contract type slug
     */
    private function getContractTypeName(string $slug): string
    {
        $names = [
            'erpr1' => 'ЕРПР 1',
            'erpr2' => 'ЕРПР 2',
            'erpr3' => 'ЕРПР 3',
            '90days' => '90 дни',
            '9months' => '9 месеца',
        ];

        return $names[$slug] ?? $slug;
    }
}
