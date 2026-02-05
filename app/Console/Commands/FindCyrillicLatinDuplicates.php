<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindCyrillicLatinDuplicates extends Command
{
    protected $signature = 'candidates:find-cyrillic-duplicates';

    protected $description = 'Find duplicate candidates that were missed due to Cyrillic/Latin character differences in passport numbers';

    // Cyrillic to Latin mapping for characters that look identical
    private array $cyrillicToLatin = [
        'А' => 'A', 'В' => 'B', 'С' => 'C', 'Е' => 'E', 'Н' => 'H',
        'К' => 'K', 'М' => 'M', 'О' => 'O', 'Р' => 'P', 'Т' => 'T',
        'Х' => 'X', 'У' => 'Y',
        'а' => 'a', 'в' => 'b', 'с' => 'c', 'е' => 'e', 'н' => 'h',
        'к' => 'k', 'м' => 'm', 'о' => 'o', 'р' => 'p', 'т' => 't',
        'х' => 'x', 'у' => 'y',
    ];

    public function handle(): int
    {
        $this->info('Finding candidates with potential Cyrillic/Latin passport duplicates...');
        $this->info('');

        // Get all non-deleted candidates with passport records
        $candidates = DB::table('candidates AS c')
            ->join('candidate_passports AS cp', 'cp.candidate_id', '=', 'c.id')
            ->whereNull('c.deleted_at')
            ->whereNotNull('cp.passport_number')
            ->where('cp.passport_number', '!=', '')
            ->select('c.id', 'c.fullName', 'c.birthday', 'c.created_at', 'cp.passport_number')
            ->orderBy('c.id')
            ->get();

        $this->info("Found {$candidates->count()} candidates with passports");
        $this->info('');

        // Group by normalized passport number
        $groups = [];
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizePassport($candidate->passport_number);
            $groups[$normalized][] = $candidate;
        }

        // Find groups with multiple candidates (duplicates)
        $duplicateGroups = array_filter($groups, fn($group) => count($group) > 1);

        // Further filter: only groups where passport numbers are actually different (Cyrillic vs Latin)
        $cyrillicLatinDuplicates = [];
        foreach ($duplicateGroups as $normalized => $group) {
            $passports = array_unique(array_column($group, 'passport_number'));
            if (count($passports) > 1) {
                $cyrillicLatinDuplicates[$normalized] = $group;
            }
        }

        if (empty($cyrillicLatinDuplicates)) {
            $this->info('No Cyrillic/Latin passport duplicates found.');
            return Command::SUCCESS;
        }

        $this->warn('Found ' . count($cyrillicLatinDuplicates) . ' groups of Cyrillic/Latin duplicates:');
        $this->info('');

        foreach ($cyrillicLatinDuplicates as $normalized => $group) {
            $this->line("=== Normalized: {$normalized} ===");

            // Sort by created_at to show oldest first
            usort($group, fn($a, $b) => $a->created_at <=> $b->created_at);

            foreach ($group as $i => $candidate) {
                $marker = $i === 0 ? '[OLDEST]' : '';
                $this->line("  #{$candidate->id} {$candidate->fullName} ({$candidate->birthday})");
                $this->line("    Passport: {$candidate->passport_number} {$marker}");
                $this->line("    Created: {$candidate->created_at}");
            }
            $this->line('');
        }

        // Summary table
        $this->info('=== Summary ===');
        $tableData = [];
        foreach ($cyrillicLatinDuplicates as $normalized => $group) {
            usort($group, fn($a, $b) => $a->created_at <=> $b->created_at);
            $ids = array_column($group, 'id');
            $passports = array_column($group, 'passport_number');
            $tableData[] = [
                'IDs' => implode(', ', $ids),
                'Name' => $group[0]->fullName,
                'Birthday' => $group[0]->birthday,
                'Passports' => implode(' / ', array_unique($passports)),
            ];
        }

        $this->table(['IDs', 'Name', 'Birthday', 'Passports'], $tableData);

        return Command::SUCCESS;
    }

    private function normalizePassport(string $passport): string
    {
        // Convert Cyrillic look-alikes to Latin
        $normalized = strtr($passport, $this->cyrillicToLatin);

        // Uppercase and remove spaces
        $normalized = strtoupper(str_replace(' ', '', $normalized));

        return $normalized;
    }
}
