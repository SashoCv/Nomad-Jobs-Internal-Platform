# Passport Legacy Columns Migration Plan

## Overview

Migrate from legacy passport columns on `candidates` table to the dedicated `candidate_passports` table.

## Current State Analysis

### Legacy Columns (on `candidates` table)
| Column | Type | Description |
|--------|------|-------------|
| `passport` | string | Passport number |
| `passportValidUntil` | date | Expiry date |
| `passportIssuedOn` | date | Issue date |
| `passportIssuedBy` | string | Issuing authority |
| `passportPath` | string | File storage path |
| `passportName` | string | Original filename |

### New Table (`candidate_passports`)
| Column | Type | Description |
|--------|------|-------------|
| `id` | int | Primary key |
| `candidate_id` | int | FK to candidates |
| `passport_number` | string | Passport number |
| `expiry_date` | date | Expiry date |
| `issue_date` | date | Issue date |
| `issued_by` | string | Issuing authority |
| `file_path` | string | File storage path |
| `file_name` | string | Original filename |
| `notes` | string | Additional notes |

### Data Status (as of Jan 29, 2026)
- Total candidates: **7,396**
- Passport records in new table: **7,533**
- Candidates without passport record: **131** (BUG - new candidates after Jan 22)
- Records with data mismatches: **50** (mostly `issued_by` translations)
- Duplicate passport records: **0** (good)

---

## COMPLETED: Bug Fixes (Jan 29, 2026)

### Bug 1: New candidates not getting passport records

**Problem**: `handleFileUploads()` only created `CandidatePassport` records when a file was uploaded, not when just text data was provided.

**Fix**: Created new `syncPassportData()` method in `CandidateService.php` that:
- Creates/updates `CandidatePassport` record whenever ANY passport data is present
- Uses `updateOrCreate()` to handle both new and existing records
- Still maintains dual-write to legacy columns for backward compatibility

**File changed**: `app/Services/CandidateService.php`

### Bug 2: Updates not syncing to new table

**Problem**: `updateCandidate()` was updating legacy columns but not the `candidate_passports` table.

**Fix**: `handleFileUploads()` now calls `syncPassportData()` which handles all passport data sync.

---

## READY TO RUN: Data Sync Migration

**Migration file**: `database/migrations/2026_01_29_100000_sync_passport_data_from_legacy.php`

This migration will:
1. Insert passport records for 131 candidates missing them
2. Update 50 mismatched records using legacy columns as source of truth

### To run on staging:
```bash
php artisan migrate --path=database/migrations/2026_01_29_100000_sync_passport_data_from_legacy.php
```

### To run on production:
```bash
php artisan migrate --path=database/migrations/2026_01_29_100000_sync_passport_data_from_legacy.php
```

### Validation queries (run after migration):
```sql
-- Should return 0 (no missing records)
SELECT COUNT(*) FROM candidates c
LEFT JOIN candidate_passports cp ON c.id = cp.candidate_id
WHERE cp.id IS NULL AND c.deleted_at IS NULL
AND (c.passport IS NOT NULL OR c.passportValidUntil IS NOT NULL);

-- Should return 0 (no mismatches)
SELECT COUNT(*) FROM candidates c
INNER JOIN candidate_passports cp ON c.id = cp.candidate_id
WHERE c.deleted_at IS NULL
AND (
    COALESCE(c.passport, '') != COALESCE(cp.passport_number, '')
    OR COALESCE(c.passportValidUntil, '1900-01-01') != COALESCE(cp.expiry_date, '1900-01-01')
    OR COALESCE(c.passportIssuedOn, '1900-01-01') != COALESCE(cp.issue_date, '1900-01-01')
);
```

---

## FUTURE PHASES

### Phase 2: Code Updates (Read from new table)

Update frontend and backend to read passport data from `candidate_passports` instead of legacy columns.

**Backend files to update (35 files)**:
- `CandidateResource.php` - Return passport from relationship
- `CvGeneratorService.php` - Read from relationship
- `CvDocxGeneratorService.php` - Read from relationship
- `candidatesExport.blade.php` - Read from relationship
- `cv.blade.php` - Read from relationship

**Frontend files to update (57 files)**:
- Types: `person.types.ts`, interfaces
- Components: `PassportCard`, forms
- Hooks: `use-person-query`, etc.

### Phase 3: Remove Dual Write

After Phase 2 is deployed and verified:
- Remove passport fields from `$candidate->fill($data)`
- Remove legacy column updates in `syncPassportData()`

### Phase 4: Drop Legacy Columns

**ONLY after Phase 3 has been in production for 2+ weeks**

```php
Schema::table('candidates', function (Blueprint $table) {
    $table->dropColumn([
        'passport',
        'passportValidUntil',
        'passportIssuedOn',
        'passportIssuedBy',
        'passportPath',
        'passportName'
    ]);
});
```

---

## Deployment Checklist

### Immediate (Jan 29, 2026)
- [x] Fix `handleFileUploads()` bug in `CandidateService.php`
- [x] Create `syncPassportData()` method
- [x] Create data sync migration
- [ ] Deploy code fix to staging
- [ ] Run migration on staging
- [ ] Validate on staging
- [ ] Deploy code fix to production
- [ ] Run migration on production
- [ ] Validate on production

### Later (Phase 2-4)
- [ ] Update backend to read from new table
- [ ] Update frontend to read from new table
- [ ] Remove dual write
- [ ] Drop legacy columns

---

## Rollback Plan

### If code fix causes issues:
- Revert `CandidateService.php` changes
- Data is still safe in legacy columns

### If migration causes issues:
- Data sync is additive/update only
- No data is deleted
- Legacy columns remain unchanged
