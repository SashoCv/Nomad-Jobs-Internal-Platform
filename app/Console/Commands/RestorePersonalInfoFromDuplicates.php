<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RestorePersonalInfoFromDuplicates extends Command
{
    protected $signature = 'candidates:restore-personal-info {--dry-run : Show what would be done without making changes}';

    protected $description = 'Restore personal info from soft-deleted duplicates to master profiles after the contracts migration';

    private array $personalFields = [
        'fullName',
        'fullNameCyrillic',
        'email',
        'phoneNumber',
        'gender',
        'birthday',
        'nationality',
        'country',
        'country_id',
        'placeOfBirth',
        'address',
        'area',
        'areaOfResidence',
        'addressOfResidence',
        'periodOfResidence',
        'height',
        'weight',
        'personPicturePath',
        'personPictureName',
        'chronic_diseases',
        'martialStatus',
        'children_info',
        'education',
        'specialty',
        'qualification',
        'english_level',
        'russian_level',
        'other_language',
        'other_language_level',
        'has_driving_license',
        'driving_license_category',
        'driving_license_expiry',
        'driving_license_country',
        'country_of_visa_application',
    ];

    private array $junkValues = ['.', '-', '--', '[]', '[empty]', '..', '...', 'NULL'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE — no changes will be made');
            $this->info('');
        }

        $migrationStart = '2026-01-28 00:00:00';
        $migrationEnd = '2026-02-01 00:00:00';
        $modifiedCutoff = '2026-01-30 00:00:00';

        $masters = DB::table('candidates AS m')
            ->select('m.id AS master_id')
            ->join('candidates AS d', function ($join) use ($migrationStart, $migrationEnd) {
                $join->on(DB::raw('LOWER(TRIM(m.fullName))'), '=', DB::raw('LOWER(TRIM(d.fullName))'))
                    ->on('m.birthday', '=', 'd.birthday')
                    ->on('m.id', '!=', 'd.id')
                    ->whereNotNull('d.deleted_at')
                    ->where('d.deleted_at', '>=', $migrationStart)
                    ->where('d.deleted_at', '<=', $migrationEnd)
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
        $skippedCount = 0;

        foreach ($masters as $masterId) {
            $master = DB::table('candidates')->where('id', $masterId)->first();
            if (!$master) {
                continue;
            }

            $newestDuplicate = DB::table('candidates')
                ->where('id', '!=', $masterId)
                ->whereRaw('LOWER(TRIM(fullName)) = ?', [strtolower(trim($master->fullName))])
                ->where('birthday', '=', $master->birthday)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '>=', $migrationStart)
                ->where('deleted_at', '<=', $migrationEnd)
                ->where('created_at', '>', $master->created_at)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$newestDuplicate) {
                $skippedCount++;
                continue;
            }

            $isModifiedByUser = $master->updated_at > $modifiedCutoff;
            $tier = $isModifiedByUser ? 2 : 1;
            $updateData = [];
            $changedFields = [];

            foreach ($this->personalFields as $field) {
                $dupValue = $newestDuplicate->$field ?? null;
                $masterValue = $master->$field ?? null;

                if ($this->isEmptyOrJunk($dupValue)) {
                    continue;
                }

                if ($isModifiedByUser) {
                    if ($this->isEmptyOrJunk($masterValue)) {
                        $updateData[$field] = $dupValue;
                        $changedFields[] = $field;
                    }
                } else {
                    if ($masterValue != $dupValue) {
                        $updateData[$field] = $dupValue;
                        $changedFields[] = $field;
                    }
                }
            }

            if (!empty($updateData)) {
                $updatedCount++;

                $this->line("Master #{$masterId} ({$master->fullName}) <- Dup #{$newestDuplicate->id} [Tier {$tier}]");
                foreach ($changedFields as $field) {
                    $oldVal = $master->$field ?? '[null]';
                    $newVal = $updateData[$field];
                    $this->line("  {$field}: {$oldVal} -> {$newVal}");
                }
                $this->line('');

                if (!$dryRun) {
                    $updateData['updated_at'] = Carbon::now();
                    DB::table('candidates')
                        ->where('id', $masterId)
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

        $this->info('=== Summary ===');
        $this->info("Tier 1 (untouched): {$tier1Count}");
        $this->info("Tier 2 (modified):  {$tier2Count}");
        $this->info("Updated:            {$updatedCount}");
        $this->info("Skipped (no diff):  {$skippedCount}");

        if ($dryRun) {
            $this->info('');
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
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
