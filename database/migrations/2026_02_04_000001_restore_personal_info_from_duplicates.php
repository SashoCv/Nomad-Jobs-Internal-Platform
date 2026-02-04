<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Personal info fields to restore from soft-deleted duplicates.
     * These are NOT contract-related fields.
     */
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

    /**
     * Values considered junk — should not overwrite real data.
     */
    private array $junkValues = ['.', '-', '--', '[]', '[empty]', '..', '...', 'NULL'];

    /**
     * Run the migrations.
     *
     * Restores personal info from soft-deleted duplicates to master profiles.
     * The original contracts migration (2026_01_28_000003) kept the oldest
     * profile's personal info, discarding corrections from newer duplicates.
     *
     * Two-tier approach:
     *   Tier 1 (untouched masters): overwrite with duplicate's non-null/non-junk values
     *   Tier 2 (user-modified masters): only fill NULL/empty fields
     */
    public function up(): void
    {
        $migrationStart = '2026-01-28 00:00:00';
        $migrationEnd = '2026-02-01 00:00:00';
        $modifiedCutoff = '2026-01-30 00:00:00';

        // Find all master-duplicate pairs
        // For each master, get the newest soft-deleted duplicate
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

        Log::info('Personal info restore: found master profiles with duplicates', [
            'total_masters' => $masters->count(),
        ]);

        $tier1Count = 0;
        $tier2Count = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($masters as $masterId) {
            $master = DB::table('candidates')->where('id', $masterId)->first();
            if (!$master) {
                continue;
            }

            // Find the newest soft-deleted duplicate for this master
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
            $updateData = [];
            $changedFields = [];

            foreach ($this->personalFields as $field) {
                $dupValue = $newestDuplicate->$field ?? null;
                $masterValue = $master->$field ?? null;

                // Skip if duplicate value is null, empty, or junk
                if ($this->isEmptyOrJunk($dupValue)) {
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
                $updateData['updated_at'] = Carbon::now();

                DB::table('candidates')
                    ->where('id', $masterId)
                    ->update($updateData);

                $updatedCount++;

                Log::info('Personal info restored', [
                    'master_id' => $masterId,
                    'duplicate_id' => $newestDuplicate->id,
                    'tier' => $isModifiedByUser ? 2 : 1,
                    'fields_updated' => $changedFields,
                ]);
            } else {
                $skippedCount++;
            }

            if ($isModifiedByUser) {
                $tier2Count++;
            } else {
                $tier1Count++;
            }
        }

        Log::info('Personal info restore completed', [
            'tier1_processed' => $tier1Count,
            'tier2_processed' => $tier2Count,
            'actually_updated' => $updatedCount,
            'skipped_no_changes' => $skippedCount,
        ]);
    }

    /**
     * Check if a value is null, empty string, or a known junk value.
     */
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

    /**
     * Reverse the migrations.
     *
     * This migration is a data fix and cannot be automatically reversed.
     */
    public function down(): void
    {
        Log::warning('Cannot automatically reverse personal info restore. Use a database backup to revert.');
    }
};
