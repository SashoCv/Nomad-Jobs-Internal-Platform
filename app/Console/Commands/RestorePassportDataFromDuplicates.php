<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RestorePassportDataFromDuplicates extends Command
{
    protected $signature = 'candidates:restore-passport-data {--dry-run : Show what would be done without making changes}';

    protected $description = 'Restore passport data from soft-deleted duplicates to master profiles after the contracts migration';

    private array $passportFields = [
        'passport_number',
        'expiry_date',
        'issue_date',
        'issued_by',
        'file_path',
        'file_name',
    ];

    private array $junkValues = ['.', '-', '--', '[]', '[empty]', '..', '...', 'NULL'];

    private string $migrationStart = '2026-01-28 00:00:00';
    private string $migrationEnd = '2026-02-01 00:00:00';
    private string $modifiedCutoff = '2026-01-30 00:00:00';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE — no changes will be made');
            $this->info('');
        }

        $masters = DB::table('candidates AS m')
            ->select('m.id AS master_id')
            ->join('candidates AS d', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(m.fullName))'), '=', DB::raw('LOWER(TRIM(d.fullName))'))
                    ->on('m.birthday', '=', 'd.birthday')
                    ->on('m.id', '!=', 'd.id')
                    ->whereNotNull('d.deleted_at')
                    ->where('d.deleted_at', '>=', $this->migrationStart)
                    ->where('d.deleted_at', '<=', $this->migrationEnd)
                    ->where(DB::raw('d.created_at'), '>', DB::raw('m.created_at'));
            })
            ->whereNull('m.deleted_at')
            ->groupBy('m.id')
            ->pluck('master_id');

        $this->info("Found {$masters->count()} master profiles with soft-deleted duplicates");
        $this->info('');

        $tier1Count = 0;
        $tier2Count = 0;
        $updatedCount = 0;
        $filledCount = 0;
        $skippedCount = 0;

        foreach ($masters as $masterId) {
            $master = DB::table('candidates')->where('id', $masterId)->first();
            if (!$master) {
                continue;
            }

            $masterPassport = DB::table('candidate_passports')
                ->where('candidate_id', $masterId)
                ->first();

            // Find the newest duplicate that has a passport record
            $newestDupWithPassport = DB::table('candidates AS d')
                ->join('candidate_passports AS dp', 'dp.candidate_id', '=', 'd.id')
                ->where('d.id', '!=', $masterId)
                ->whereRaw('LOWER(TRIM(d.fullName)) = ?', [strtolower(trim($master->fullName))])
                ->where('d.birthday', '=', $master->birthday)
                ->whereNotNull('d.deleted_at')
                ->where('d.deleted_at', '>=', $this->migrationStart)
                ->where('d.deleted_at', '<=', $this->migrationEnd)
                ->where('d.created_at', '>', $master->created_at)
                ->orderBy('d.created_at', 'desc')
                ->select('d.id AS dup_id', 'dp.*')
                ->first();

            if (!$newestDupWithPassport) {
                $skippedCount++;
                continue;
            }

            if (!$masterPassport) {
                // Master has no passport at all — insert from duplicate
                $dupFilePath = $newestDupWithPassport->file_path;
                $dupFileExists = $dupFilePath ? Storage::disk('public')->exists($dupFilePath) : false;

                $this->line("Master #{$masterId} ({$master->fullName}) — NO PASSPORT, copying from Dup #{$newestDupWithPassport->dup_id}");
                $this->line("  passport_number: {$newestDupWithPassport->passport_number}");
                $this->line("  expiry_date: {$newestDupWithPassport->expiry_date}");
                $this->line("  file_path: {$dupFilePath}" . ($dupFilePath ? ($dupFileExists ? ' [EXISTS]' : ' [MISSING]') : ''));
                $this->line('');

                if (!$dryRun) {
                    DB::table('candidate_passports')->insert([
                        'candidate_id' => $masterId,
                        'passport_number' => $newestDupWithPassport->passport_number,
                        'issue_date' => $newestDupWithPassport->issue_date,
                        'expiry_date' => $newestDupWithPassport->expiry_date,
                        'issued_by' => $newestDupWithPassport->issued_by,
                        'file_path' => $dupFileExists ? $dupFilePath : null,
                        'file_name' => $dupFileExists ? $newestDupWithPassport->file_name : null,
                        'notes' => $newestDupWithPassport->notes,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                $filledCount++;
                continue;
            }

            // Both have passports — apply two-tier logic
            $isModifiedByUser = $masterPassport->updated_at > $this->modifiedCutoff;
            $tier = $isModifiedByUser ? 2 : 1;
            $updateData = [];
            $changedFields = [];

            // Pre-check file existence for file_path fields
            $dupFilePath = $newestDupWithPassport->file_path ?? null;
            $masterFilePath = $masterPassport->file_path ?? null;
            $dupFileExists = $dupFilePath ? Storage::disk('public')->exists($dupFilePath) : false;
            $masterFileExists = $masterFilePath ? Storage::disk('public')->exists($masterFilePath) : false;

            foreach ($this->passportFields as $field) {
                $dupValue = $newestDupWithPassport->$field ?? null;
                $masterValue = $masterPassport->$field ?? null;

                if ($this->isEmptyOrJunk($dupValue)) {
                    continue;
                }

                // File fields: skip if dup file doesn't exist on disk
                if (($field === 'file_path' || $field === 'file_name') && !$dupFileExists) {
                    continue;
                }

                // File fields: if master already has a file, only overwrite
                // when the dup file is genuinely different (not a _passport
                // artifact from the passport migration 110000).
                if ($field === 'file_path' || $field === 'file_name') {
                    if ($this->isEmptyOrJunk($masterValue)) {
                        // Master has no file — always fill
                        $updateData[$field] = $dupValue;
                        $changedFields[] = $field;
                        continue;
                    }
                    if ($isModifiedByUser) {
                        // Tier 2: don't overwrite existing files
                        continue;
                    }
                    // Tier 1: only overwrite if dup file is NOT a migration artifact
                    if ($this->isPassportFileArtifact($dupFilePath, $masterFilePath)) {
                        continue;
                    }
                    $updateData[$field] = $dupValue;
                    $changedFields[] = $field;
                    continue;
                }

                if ($isModifiedByUser) {
                    // Tier 2: only fill NULL/empty fields on master
                    if ($this->isEmptyOrJunk($masterValue)) {
                        $updateData[$field] = $dupValue;
                        $changedFields[] = $field;
                    }
                } else {
                    // Tier 1: overwrite if values differ
                    if ($masterValue != $dupValue) {
                        $updateData[$field] = $dupValue;
                        $changedFields[] = $field;
                    }
                }
            }

            if (!empty($updateData)) {
                $updatedCount++;

                $this->line("Master #{$masterId} ({$master->fullName}) passport <- Dup #{$newestDupWithPassport->dup_id} [Tier {$tier}]");
                foreach ($changedFields as $field) {
                    $oldVal = $masterPassport->$field ?? '[null]';
                    $newVal = $updateData[$field];
                    $fileStatus = '';
                    if ($field === 'file_path') {
                        $fileStatus .= $masterFileExists ? ' [old EXISTS]' : ($masterFilePath ? ' [old MISSING]' : '');
                        $fileStatus .= $dupFileExists ? ' [new EXISTS]' : ' [new MISSING]';
                    }
                    $this->line("  {$field}: {$oldVal} -> {$newVal}{$fileStatus}");
                }
                $this->line('');

                if (!$dryRun) {
                    $updateData['updated_at'] = Carbon::now();
                    DB::table('candidate_passports')
                        ->where('id', $masterPassport->id)
                        ->update($updateData);
                }
            } else {
                $skippedCount++;
            }

            if ($isModifiedByUser) {
                $tier2Count++;
            } else {
                $tier1Count++;
            }
        }

        $this->info('=== Passport Summary ===');
        $this->info("Tier 1 (untouched): {$tier1Count}");
        $this->info("Tier 2 (modified):  {$tier2Count}");
        $this->info("New passports:      {$filledCount}");
        $this->info("Updated:            {$updatedCount}");
        $this->info("Skipped (no diff):  {$skippedCount}");

        if ($dryRun) {
            $this->info('');
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Check if the duplicate's file_path is a migration artifact of the master's.
     *
     * The passport migration (110000) copied files from the `files` table to
     * candidate/{id}/passport/{name}. Filenames often got _passport suffixes
     * appended (e.g. "scan.jpg" -> "scan.jpg_passport.jpg"). The master got
     * the clean version; the duplicate got the artifact. Also, legacy paths
     * like personPassports/hash.jpg are the old storage format — the same
     * file was already migrated to the master's clean path.
     */
    private function isPassportFileArtifact(?string $dupPath, ?string $masterPath): bool
    {
        if (!$dupPath) {
            return false;
        }

        $dupBasename = basename($dupPath);

        // _passport in filename = artifact from files table migration
        if (str_contains($dupBasename, '_passport')) {
            return true;
        }

        // personPassports/ = legacy storage format, already migrated for master
        if (str_starts_with($dupPath, 'personPassports/')) {
            return true;
        }

        return false;
    }

    private function isEmptyOrJunk($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_string($value) && in_array(trim($value), $this->junkValues, true)) {
            return true;
        }

        return false;
    }
}
