<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillMissingCyrillicNames extends Command
{
    protected $signature = 'candidates:fill-cyrillic-names
                            {--dry-run : Show what would happen without making changes (default)}
                            {--execute : Actually perform the updates}';

    protected $description = 'Fill missing fullNameCyrillic for candidates by transliterating from fullName (Latin to Cyrillic)';

    /**
     * Latin to Bulgarian Cyrillic transliteration map.
     * Multi-character sequences must be checked before single characters.
     */
    private const LATIN_TO_CYRILLIC = [
        // 3-char
        'Sht' => 'Щ', 'sht' => 'щ',
        'SHT' => 'Щ',
        // 2-char
        'Zh' => 'Ж', 'zh' => 'ж', 'ZH' => 'Ж',
        'Ts' => 'Ц', 'ts' => 'ц', 'TS' => 'Ц',
        'Ch' => 'Ч', 'ch' => 'ч', 'CH' => 'Ч',
        'Sh' => 'Ш', 'sh' => 'ш', 'SH' => 'Ш',
        'Yu' => 'Ю', 'yu' => 'ю', 'YU' => 'Ю',
        'Ya' => 'Я', 'ya' => 'я', 'YA' => 'Я',
        // 1-char uppercase
        'A' => 'А', 'B' => 'Б', 'V' => 'В', 'G' => 'Г', 'D' => 'Д',
        'E' => 'Е', 'Z' => 'З', 'I' => 'И', 'Y' => 'Й', 'K' => 'К',
        'L' => 'Л', 'M' => 'М', 'N' => 'Н', 'O' => 'О', 'P' => 'П',
        'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'U' => 'У', 'F' => 'Ф',
        'H' => 'Х', 'J' => 'Дж', 'W' => 'У', 'X' => 'Кс', 'Q' => 'К',
        'C' => 'К',
        // 1-char lowercase
        'a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д',
        'e' => 'е', 'z' => 'з', 'i' => 'и', 'y' => 'й', 'k' => 'к',
        'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п',
        'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'f' => 'ф',
        'h' => 'х', 'j' => 'дж', 'w' => 'у', 'x' => 'кс', 'q' => 'к',
        'c' => 'к',
    ];

    public function handle(): int
    {
        $execute = $this->option('execute');

        if (! $execute) {
            $this->info('=== DRY RUN MODE (no changes will be made) ===');
            $this->info('Use --execute to perform the actual updates.');
            $this->info('');
        } else {
            $this->warn('=== EXECUTE MODE — changes will be written ===');
            $this->info('');
        }

        $candidates = DB::table('candidates')
            ->whereNull('deleted_at')
            ->whereNotNull('fullName')
            ->where('fullName', '!=', '')
            ->where(function ($q) {
                $q->whereNull('fullNameCyrillic')
                  ->orWhere('fullNameCyrillic', '');
            })
            ->select('id', 'fullName', 'fullNameCyrillic')
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates with missing Cyrillic names found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$candidates->count()} candidate(s) with missing Cyrillic name.");
        $this->info('');

        $updated = 0;

        foreach ($candidates as $candidate) {
            // Skip if fullName is already in Cyrillic
            if ($this->isCyrillic($candidate->fullName)) {
                $this->line("  #{$candidate->id} \"{$candidate->fullName}\" — already Cyrillic, skipping");
                continue;
            }

            $cyrillic = $this->transliterate($candidate->fullName);

            $this->line("  #{$candidate->id} \"{$candidate->fullName}\" → \"{$cyrillic}\"");

            if ($execute) {
                DB::table('candidates')
                    ->where('id', $candidate->id)
                    ->update(['fullNameCyrillic' => $cyrillic]);
            }

            $updated++;
        }

        $this->info('');
        $this->info($execute
            ? "Updated {$updated} candidate(s)."
            : "Would update {$updated} candidate(s). Run with --execute to apply.");

        return Command::SUCCESS;
    }

    /**
     * Check if a string is primarily Cyrillic characters.
     */
    private function isCyrillic(string $text): bool
    {
        $letters = preg_replace('/[\s\-\.\']/u', '', $text);
        if ($letters === '') {
            return false;
        }

        $cyrillicCount = preg_match_all('/\p{Cyrillic}/u', $letters);
        return $cyrillicCount > (mb_strlen($letters) / 2);
    }

    /**
     * Transliterate a Latin string to Cyrillic.
     */
    private function transliterate(string $text): string
    {
        $result = '';
        $len = mb_strlen($text);
        $i = 0;

        while ($i < $len) {
            $matched = false;

            // Try 3-char, then 2-char, then 1-char sequences
            foreach ([3, 2, 1] as $size) {
                $chunk = mb_substr($text, $i, $size);

                if (isset(self::LATIN_TO_CYRILLIC[$chunk])) {
                    $result .= self::LATIN_TO_CYRILLIC[$chunk];
                    $i += $size;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                // Keep character as-is (spaces, hyphens, digits, etc.)
                $result .= mb_substr($text, $i, 1);
                $i++;
            }
        }

        return $result;
    }
}
