<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table for pricing ↔ contract_types
        Schema::create('company_pricing_contract_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_id');
            $table->unsignedBigInteger('contract_type_id');
            $table->timestamps();

            $table->unique(['pricing_id', 'contract_type_id'], 'cpct_unique');
            $table->foreign('pricing_id', 'cpct_pricing_fk')
                ->references('id')->on('contract_pricings')->onDelete('cascade');
            $table->foreign('contract_type_id', 'cpct_contract_type_fk')
                ->references('id')->on('contract_types')->onDelete('cascade');
        });

        // 2. Migrate: for each company, merge multiple contracts into one
        //    and move contract_type to pricing items
        $companies = DB::table('company_service_contracts')
            ->whereNull('deleted_at')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            $contracts = DB::table('company_service_contracts')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->orderByRaw("FIELD(status, 'active', 'pending', 'expired', 'terminated')")
                ->orderBy('id', 'desc')
                ->get();

            if ($contracts->count() <= 1) {
                // Single contract - just populate pivot from agreement_type
                $contract = $contracts->first();
                if ($contract) {
                    $this->populatePivotForContract($contract);
                }
                continue;
            }

            // Multiple contracts - pick master (prefer active, then newest)
            $master = $contracts->first();

            foreach ($contracts as $contract) {
                if ($contract->id === $master->id) {
                    // Populate pivot for master's existing pricing items
                    $this->populatePivotForContract($contract);
                    continue;
                }

                // Move pricing items from duplicate to master
                $pricingIds = DB::table('contract_pricings')
                    ->where('company_service_contract_id', $contract->id)
                    ->whereNull('deleted_at')
                    ->pluck('id');

                // Populate pivot BEFORE moving (using the duplicate's agreement_type)
                $this->populatePivotForContract($contract);

                // Move pricing items to master contract
                DB::table('contract_pricings')
                    ->where('company_service_contract_id', $contract->id)
                    ->whereNull('deleted_at')
                    ->update(['company_service_contract_id' => $master->id]);

                // Soft-delete the duplicate contract
                DB::table('company_service_contracts')
                    ->where('id', $contract->id)
                    ->update(['deleted_at' => now()]);

                Log::info("Merged company service contract #{$contract->id} into #{$master->id} for company #{$companyId}");
            }
        }
    }

    /**
     * Populate pivot table entries for a contract's pricing items based on its agreement_type.
     */
    private function populatePivotForContract(object $contract): void
    {
        $slugs = match ($contract->agreement_type) {
            'erpr' => ['erpr1', 'erpr2', 'erpr3'],
            '90days' => ['90days'],
            default => [],
        };

        if (empty($slugs)) {
            return;
        }

        $contractTypeIds = DB::table('contract_types')
            ->whereIn('slug', $slugs)
            ->pluck('id');

        $pricingIds = DB::table('contract_pricings')
            ->where('company_service_contract_id', $contract->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($pricingIds as $pricingId) {
            foreach ($contractTypeIds as $contractTypeId) {
                DB::table('company_pricing_contract_types')->insertOrIgnore([
                    'pricing_id' => $pricingId,
                    'contract_type_id' => $contractTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_pricing_contract_types');
    }
};
