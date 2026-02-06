<?php

namespace App\Console\Commands;

use App\Helpers\PassportNormalizer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeCyrillicDuplicates extends Command
{
    protected $signature = 'candidates:merge-cyrillic-duplicates
                            {--dry-run : Show what would happen without making changes (default)}
                            {--execute : Actually perform the merge}';

    protected $description = 'Merge duplicate candidate profiles caused by Cyrillic/Latin passport mismatches';

    public function handle(): int
    {
        $execute = $this->option('execute');

        if (! $execute) {
            $this->info('=== DRY RUN MODE (no changes will be made) ===');
            $this->info('Use --execute to perform the actual merge.');
            $this->info('');
        } else {
            $this->warn('=== EXECUTE MODE — changes will be committed ===');
            $this->info('');
        }

        $groups = $this->findDuplicateGroups();

        if (empty($groups)) {
            $this->info('No Cyrillic/Latin passport duplicates found.');
            return Command::SUCCESS;
        }

        $this->info('Found ' . count($groups) . ' duplicate group(s).');
        $this->info('');

        $totalDuplicates = 0;

        if ($execute) {
            DB::beginTransaction();
        }

        try {
            foreach ($groups as $normalized => $candidates) {
                $master = $this->pickMaster($candidates);
                $duplicates = array_filter($candidates, fn ($c) => $c->id !== $master->id);
                $totalDuplicates += count($duplicates);

                $this->printGroup($normalized, $candidates, $master);

                if ($execute) {
                    $this->mergeGroup($master, $duplicates);
                }
            }

            if ($execute) {
                $this->normalizeAllPassports();
                DB::commit();
                $this->info('');
                $this->info('=== Merge complete ===');
            } else {
                $this->info('');
                $this->info('=== Dry-run summary ===');
            }

            $this->info("Total groups:     " . count($groups));
            $this->info("Total duplicates:  {$totalDuplicates}");
            $this->info("Unique masters:    " . count($groups));

        } catch (\Throwable $e) {
            if ($execute) {
                DB::rollBack();
            }
            $this->error("Error: {$e->getMessage()}");
            Log::error('MergeCyrillicDuplicates failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Find groups of candidates whose passports differ only by Cyrillic/Latin characters.
     */
    private function findDuplicateGroups(): array
    {
        $candidates = DB::table('candidates AS c')
            ->join('candidate_passports AS cp', 'cp.candidate_id', '=', 'c.id')
            ->leftJoin('statushistories AS sh', function ($join) {
                $join->on('sh.candidate_id', '=', 'c.id')
                    ->whereRaw('sh.id = (SELECT MAX(sh2.id) FROM statushistories AS sh2 WHERE sh2.candidate_id = c.id)');
            })
            ->leftJoin('statuses AS s', 's.id', '=', 'sh.status_id')
            ->whereNull('c.deleted_at')
            ->whereNotNull('cp.passport_number')
            ->where('cp.passport_number', '!=', '')
            ->select(
                'c.id',
                'c.fullName',
                'c.birthday',
                'c.created_at',
                'cp.passport_number',
                's.nameOfStatus AS status_name',
                's.order AS status_order',
                'sh.created_at AS last_status_date'
            )
            ->orderBy('c.id')
            ->get();

        // Group by normalized passport
        $groups = [];
        foreach ($candidates as $candidate) {
            $normalized = PassportNormalizer::normalize($candidate->passport_number);
            $groups[$normalized][] = $candidate;
        }

        // Keep only groups with multiple candidates where raw passports differ
        $duplicateGroups = [];
        foreach ($groups as $normalized => $group) {
            if (count($group) <= 1) {
                continue;
            }
            $passports = array_unique(array_column($group, 'passport_number'));
            if (count($passports) > 1) {
                $duplicateGroups[$normalized] = $group;
            }
        }

        return $duplicateGroups;
    }

    /**
     * Pick the master profile from a group:
     * 1. Highest status order
     * 2. Most recent last status date
     * 3. Newest created_at as final tiebreaker
     */
    private function pickMaster(array $candidates): object
    {
        usort($candidates, function ($a, $b) {
            $orderA = (int) ($a->status_order ?? 0);
            $orderB = (int) ($b->status_order ?? 0);

            if ($orderA !== $orderB) {
                return $orderB <=> $orderA; // highest first
            }

            $statusDateA = $a->last_status_date ?? '1970-01-01';
            $statusDateB = $b->last_status_date ?? '1970-01-01';

            if ($statusDateA !== $statusDateB) {
                return $statusDateB <=> $statusDateA; // most recent first
            }

            return $b->created_at <=> $a->created_at; // newest first
        });

        return $candidates[0];
    }

    /**
     * Print group details for both dry-run and execute modes.
     */
    private function printGroup(string $normalized, array $candidates, object $master): void
    {
        $this->line("=== Normalized passport: {$normalized} ===");

        // Collect all contracts across all candidates in the group
        $allContracts = [];

        foreach ($candidates as $c) {
            $isMaster = $c->id === $master->id;
            $tag = $isMaster ? ' [MASTER]' : ' [DUPLICATE → will merge into #' . $master->id . ']';
            $status = $c->status_name ?? 'N/A';
            $order = $c->status_order ?? 'N/A';

            $this->line("  #{$c->id} {$c->fullName} ({$c->birthday})");
            $this->line("    Passport: {$c->passport_number}");
            $lastStatusDate = $c->last_status_date ?? 'N/A';
            $this->line("    Status:   {$status} (order: {$order}) | last status date: {$lastStatusDate}");
            $this->line("    Created:  {$c->created_at}{$tag}");

            // Fetch contracts for this candidate
            $contracts = DB::table('candidate_contracts AS cc')
                ->leftJoin('companies AS co', 'co.id', '=', 'cc.company_id')
                ->leftJoin('statuses AS s', 's.id', '=', 'cc.status_id')
                ->where('cc.candidate_id', $c->id)
                ->select(
                    'cc.id',
                    'cc.contract_period_number',
                    'cc.is_active',
                    'cc.start_contract_date',
                    'cc.end_contract_date',
                    'cc.created_at',
                    'co.nameOfCompany AS company_name',
                    's.nameOfStatus AS status_name'
                )
                ->orderBy('cc.created_at', 'asc')
                ->get();

            if ($contracts->isEmpty()) {
                $this->line("    Contracts: none");
            } else {
                foreach ($contracts as $ct) {
                    $active = $ct->is_active ? ' [ACTIVE]' : '';
                    $period = $ct->start_contract_date && $ct->end_contract_date
                        ? "{$ct->start_contract_date} → {$ct->end_contract_date}"
                        : 'no dates';
                    $this->line("    Contract #{$ct->id} (period {$ct->contract_period_number}): {$ct->company_name} | {$ct->status_name} | {$period}{$active}");
                    $allContracts[] = $ct;
                }
            }
        }

        // Preview merged contract list
        if (count($candidates) > 1 && ! empty($allContracts)) {
            usort($allContracts, fn ($a, $b) => $a->created_at <=> $b->created_at);

            $this->line("  -- After merge on master #{$master->id}:");
            foreach ($allContracts as $i => $ct) {
                $newPeriod = $i + 1;
                $isLast = $i === count($allContracts) - 1;
                $activeTag = $isLast ? ' [ACTIVE]' : '';
                $period = $ct->start_contract_date && $ct->end_contract_date
                    ? "{$ct->start_contract_date} → {$ct->end_contract_date}"
                    : 'no dates';
                $this->line("     Period {$newPeriod}: Contract #{$ct->id} | {$ct->company_name} | {$ct->status_name} | {$period}{$activeTag}");
            }
        }

        $reason = $this->masterReason($master, $candidates);
        $this->line("  -> Master: #{$master->id} ({$reason})");
        $this->line('');
    }

    /**
     * Describe why this candidate was chosen as master.
     */
    private function masterReason(object $master, array $candidates): string
    {
        $orders = array_map(fn ($c) => (int) ($c->status_order ?? 0), $candidates);
        $maxOrder = max($orders);
        $countAtMax = count(array_filter($orders, fn ($o) => $o === $maxOrder));

        if ($countAtMax === 1) {
            return "highest status order: {$maxOrder}";
        }

        // Check if last_status_date broke the tie
        $atMax = array_filter($candidates, fn ($c) => (int) ($c->status_order ?? 0) === $maxOrder);
        $statusDates = array_map(fn ($c) => $c->last_status_date ?? '1970-01-01', $atMax);
        $uniqueDates = array_unique($statusDates);

        if (count($uniqueDates) > 1) {
            return "status order tied at {$maxOrder}, most recent status date: {$master->last_status_date}";
        }

        return "status order tied at {$maxOrder}, same status date, newest created_at";
    }

    /**
     * Merge a group: reassign contracts, satellite tables, passports; soft-delete duplicates.
     */
    private function mergeGroup(object $master, array $duplicates): void
    {
        $masterId = $master->id;

        foreach ($duplicates as $duplicate) {
            $duplicateId = $duplicate->id;

            Log::info('MergeCyrillicDuplicates: merging duplicate into master', [
                'master_id' => $masterId,
                'duplicate_id' => $duplicateId,
                'master_passport' => $master->passport_number,
                'duplicate_passport' => $duplicate->passport_number,
            ]);

            // 1. Re-assign contracts — offset period numbers first to avoid unique constraint
            $maxPeriod = (int) DB::table('candidate_contracts')
                ->where('candidate_id', $masterId)
                ->max('contract_period_number') ?? 0;

            $dupContracts = DB::table('candidate_contracts')
                ->where('candidate_id', $duplicateId)
                ->orderBy('contract_period_number')
                ->pluck('id');

            foreach ($dupContracts as $i => $contractId) {
                DB::table('candidate_contracts')
                    ->where('id', $contractId)
                    ->update([
                        'candidate_id' => $masterId,
                        'contract_period_number' => $maxPeriod + $i + 1,
                    ]);
            }

            // 2. Re-link satellite tables
            $this->relinkSatelliteTables($duplicateId, $masterId);

            // 3. Merge passport records
            $this->mergePassports($duplicateId, $masterId);

            // 4. Soft-delete duplicate
            DB::table('candidates')
                ->where('id', $duplicateId)
                ->update(['deleted_at' => Carbon::now()]);

            $this->info("  Merged #{$duplicateId} -> #{$masterId}");
        }

        // 5. Renumber contracts sequentially on master
        $this->renumberContracts($masterId);

        // 6. Ensure only most recent contract is active
        $this->fixActiveContract($masterId);

        // 7. Dual-write active contract to legacy columns
        $this->syncActiveContractToLegacyColumns($masterId);
    }

    /**
     * Re-link the 7 satellite tables from duplicate to master.
     */
    private function relinkSatelliteTables(int $duplicateId, int $masterId): void
    {
        DB::table('files')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);

        DB::table('statushistories')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);

        DB::table('agent_candidates')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);

        DB::table('invoices')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);

        if (DB::getSchemaBuilder()->hasTable('invoice_company_candidates')) {
            DB::table('invoice_company_candidates')
                ->where('candidate_id', $duplicateId)
                ->update(['candidate_id' => $masterId]);
        }

        DB::table('arrivals')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);

        DB::table('candidate_visas')
            ->where('candidate_id', $duplicateId)
            ->update(['candidate_id' => $masterId]);
    }

    /**
     * Move passport records from duplicate to master. If master already has one,
     * delete the duplicate's passport row (keep the master's record).
     */
    private function mergePassports(int $duplicateId, int $masterId): void
    {
        $masterHasPassport = DB::table('candidate_passports')
            ->where('candidate_id', $masterId)
            ->exists();

        if ($masterHasPassport) {
            DB::table('candidate_passports')
                ->where('candidate_id', $duplicateId)
                ->delete();
        } else {
            DB::table('candidate_passports')
                ->where('candidate_id', $duplicateId)
                ->update(['candidate_id' => $masterId]);
        }
    }

    /**
     * Renumber contract_period_number sequentially (1, 2, 3...) ordered by created_at.
     * First offsets all to high numbers to avoid unique constraint collisions.
     */
    private function renumberContracts(int $masterId): void
    {
        $contracts = DB::table('candidate_contracts')
            ->where('candidate_id', $masterId)
            ->orderBy('created_at', 'asc')
            ->pluck('id');

        // Offset all to high numbers first to clear the unique constraint space
        foreach ($contracts as $index => $contractId) {
            DB::table('candidate_contracts')
                ->where('id', $contractId)
                ->update(['contract_period_number' => 10000 + $index]);
        }

        // Now renumber sequentially from 1
        foreach ($contracts as $index => $contractId) {
            DB::table('candidate_contracts')
                ->where('id', $contractId)
                ->update(['contract_period_number' => $index + 1]);
        }
    }

    /**
     * Ensure only the most recent contract (highest contract_period_number) is active.
     */
    private function fixActiveContract(int $masterId): void
    {
        // Deactivate all
        DB::table('candidate_contracts')
            ->where('candidate_id', $masterId)
            ->update(['is_active' => false]);

        // Activate the latest one
        $latestId = DB::table('candidate_contracts')
            ->where('candidate_id', $masterId)
            ->orderByDesc('contract_period_number')
            ->value('id');

        if ($latestId) {
            DB::table('candidate_contracts')
                ->where('id', $latestId)
                ->update(['is_active' => true]);
        }
    }

    /**
     * Dual-write the active contract data to the master candidate's legacy columns.
     */
    private function syncActiveContractToLegacyColumns(int $masterId): void
    {
        $activeContract = DB::table('candidate_contracts')
            ->where('candidate_id', $masterId)
            ->where('is_active', true)
            ->first();

        if (! $activeContract) {
            return;
        }

        DB::table('candidates')
            ->where('id', $masterId)
            ->update([
                'company_id' => $activeContract->company_id,
                'position_id' => $activeContract->position_id,
                'status_id' => $activeContract->status_id,
                'type_id' => $activeContract->type_id,
                'contractType' => $activeContract->contract_type,
                'contractPeriod' => $activeContract->contract_period,
                'contractPeriodNumber' => $activeContract->contract_period_number,
                'contractExtensionPeriod' => $activeContract->contract_extension_period,
                'startContractDate' => $activeContract->start_contract_date,
                'endContractDate' => $activeContract->end_contract_date,
                'contractPeriodDate' => $activeContract->contract_period_date,
                'salary' => $activeContract->salary,
                'workingTime' => $activeContract->working_time,
                'workingDays' => $activeContract->working_days,
                'addressOfWork' => $activeContract->address_of_work,
                'nameOfFacility' => $activeContract->name_of_facility,
                'company_adresses_id' => $activeContract->company_adresses_id,
                'dossierNumber' => $activeContract->dossier_number,
                'quartal' => $activeContract->quartal,
                'seasonal' => $activeContract->seasonal,
                'case_id' => $activeContract->case_id,
                'agent_id' => $activeContract->agent_id,
                'user_id' => $activeContract->user_id,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Normalize ALL passport numbers in the DB (Cyrillic → Latin) so existing data
     * matches the new write-time normalization.
     */
    private function normalizeAllPassports(): void
    {
        $passports = DB::table('candidate_passports')
            ->whereNotNull('passport_number')
            ->where('passport_number', '!=', '')
            ->select('id', 'passport_number')
            ->get();

        $updated = 0;
        foreach ($passports as $row) {
            $normalized = PassportNormalizer::normalize($row->passport_number);
            if ($normalized !== $row->passport_number) {
                DB::table('candidate_passports')
                    ->where('id', $row->id)
                    ->update(['passport_number' => $normalized]);
                $updated++;
            }
        }

        $this->info("Normalized {$updated} passport number(s) in candidate_passports table.");
    }
}
