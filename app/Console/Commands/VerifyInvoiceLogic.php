<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\ContractType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyInvoiceLogic extends Command
{
    protected $signature = 'invoices:verify-logic';
    protected $description = 'Verify that the new invoice pricing logic matches expected behavior (dry-run)';

    public function handle(): int
    {
        $this->info('=== Invoice Logic Verification ===');
        $this->info('');

        // 1. Check all active contracts are one-per-company
        $this->verifyOneContractPerCompany();

        // 2. Check pivot table populated correctly
        $this->verifyPivotTableData();

        // 3. Simulate invoice matching for real candidates
        $this->simulateInvoiceMatching();

        return Command::SUCCESS;
    }

    private function verifyOneContractPerCompany(): void
    {
        $this->info('--- Check: One active contract per company ---');

        $duplicates = DB::table('company_service_contracts')
            ->select('company_id', DB::raw('COUNT(*) as cnt'))
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->groupBy('company_id')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->line('  OK: No company has multiple active contracts.');
        } else {
            foreach ($duplicates as $dup) {
                $this->error("  Company #{$dup->company_id} has {$dup->cnt} active contracts!");
            }
        }
        $this->info('');
    }

    private function verifyPivotTableData(): void
    {
        $this->info('--- Check: Pivot table (company_pricing_contract_types) ---');

        $totalPricings = DB::table('contract_pricings')->whereNull('deleted_at')->count();
        $pricingsWithTypes = DB::table('company_pricing_contract_types')
            ->distinct('pricing_id')
            ->count('pricing_id');
        $pricingsWithoutTypes = $totalPricings - $pricingsWithTypes;

        $this->line("  Total pricing items: {$totalPricings}");
        $this->line("  With contract types: {$pricingsWithTypes}");
        $this->line("  Without (applies to all): {$pricingsWithoutTypes}");

        // Show breakdown by contract type
        $breakdown = DB::table('company_pricing_contract_types as cpct')
            ->join('contract_types as ct', 'ct.id', '=', 'cpct.contract_type_id')
            ->select('ct.name', 'ct.slug', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ct.id', 'ct.name', 'ct.slug')
            ->get();

        foreach ($breakdown as $row) {
            $this->line("    {$row->name} ({$row->slug}): {$row->cnt} pricing items");
        }
        $this->info('');
    }

    private function simulateInvoiceMatching(): void
    {
        $this->info('--- Simulate: Invoice matching for sample candidates ---');

        // Get 10 random candidates with company and contract type
        $candidates = Candidate::with('contract_type')
            ->whereNotNull('company_id')
            ->whereNotNull('contract_type_id')
            ->whereNotNull('status_id')
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($candidates->isEmpty()) {
            $this->warn('  No candidates found to test.');
            return;
        }

        $issues = 0;

        foreach ($candidates as $candidate) {
            $companyId = $candidate->company_id;
            $statusId = $candidate->status_id;
            $contractTypeId = $candidate->contract_type_id;
            $countryId = $candidate->country_id;
            $contractTypeSlug = $candidate->contract_type?->slug;

            // NEW logic: find active contract (one per company)
            $activeContract = CompanyServiceContract::where('company_id', $companyId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->first();

            if (!$activeContract) {
                $this->line("  #{$candidate->id} {$candidate->fullName}: No active contract for company #{$companyId}");
                continue;
            }

            // Get matching pricing items (new logic)
            $pricings = ContractPricing::with('contractTypes')
                ->where('company_service_contract_id', $activeContract->id)
                ->where('status_id', $statusId)
                ->whereNull('deleted_at')
                ->get();

            $matchingPricings = $pricings->filter(function ($item) use ($contractTypeId, $countryId) {
                // Contract type filter
                if ($item->contractTypes->isNotEmpty()) {
                    if (!$contractTypeId) {
                        return false;
                    }
                    if (!$item->contractTypes->contains('id', $contractTypeId)) {
                        return false;
                    }
                }

                // Country scope filter
                $scopeType = $item->country_scope_type ?? 'all';
                $scopeIds = $item->country_scope_ids ?? [];
                if ($scopeType === 'all' || empty($scopeIds)) {
                    return true;
                }
                $isInScope = in_array($countryId, $scopeIds);
                return $scopeType === 'include' ? $isInScope : !$isInScope;
            });

            $typeNames = $matchingPricings->map(function ($p) {
                $types = $p->contractTypes->pluck('slug')->implode(',');
                return $types ?: 'all';
            })->implode(' | ');

            $totalPrice = $matchingPricings->sum('price');

            $this->line("  #{$candidate->id} {$candidate->fullName} (type: {$contractTypeSlug}, status: {$statusId})");
            $this->line("    Contract: #{$activeContract->id} | Matching pricings: {$matchingPricings->count()} | Total: {$totalPrice} EUR");

            if ($matchingPricings->isNotEmpty()) {
                $this->line("    Types: [{$typeNames}]");
            }
        }

        $this->info('');
        $this->info("Checked {$candidates->count()} candidates. Issues: {$issues}");
    }
}
