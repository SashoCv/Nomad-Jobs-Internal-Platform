<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find India and Nepal IDs for data migration
        $indiaId = DB::table('countries')->where('name_en', 'India')->value('id');
        $nepalId = DB::table('countries')->where('name_en', 'Nepal')->value('id');
        $indiNepalIds = array_values(array_filter([$indiaId, $nepalId]));

        // Read existing data before dropping the column
        $existingRows = DB::table('contract_pricings')
            ->select('id', 'country_scope')
            ->get();

        // Drop the old enum column
        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->dropColumn('country_scope');
        });

        // Add new flexible columns
        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->enum('country_scope_type', ['all', 'include', 'exclude'])->default('all')->after('description');
            $table->json('country_scope_ids')->nullable()->after('country_scope_type');
        });

        // Migrate existing data
        foreach ($existingRows as $row) {
            $scopeType = 'all';
            $scopeIds = null;

            if ($row->country_scope === 'india_nepal_only') {
                $scopeType = 'include';
                $scopeIds = json_encode($indiNepalIds);
            } elseif ($row->country_scope === 'except_india_nepal') {
                $scopeType = 'exclude';
                $scopeIds = json_encode($indiNepalIds);
            }

            DB::table('contract_pricings')
                ->where('id', $row->id)
                ->update([
                    'country_scope_type' => $scopeType,
                    'country_scope_ids' => $scopeIds,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indiaId = DB::table('countries')->where('name_en', 'India')->value('id');
        $nepalId = DB::table('countries')->where('name_en', 'Nepal')->value('id');
        $indiNepalIds = array_values(array_filter([$indiaId, $nepalId]));

        // Read existing data before dropping columns
        $existingRows = DB::table('contract_pricings')
            ->select('id', 'country_scope_type', 'country_scope_ids')
            ->get();

        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->dropColumn(['country_scope_type', 'country_scope_ids']);
        });

        Schema::table('contract_pricings', function (Blueprint $table) {
            $table->enum('country_scope', ['all_countries', 'india_nepal_only', 'except_india_nepal'])
                ->default('all_countries')
                ->after('description');
        });

        // Reverse data migration
        foreach ($existingRows as $row) {
            $scope = 'all_countries';
            $ids = $row->country_scope_ids ? json_decode($row->country_scope_ids, true) : [];

            if ($row->country_scope_type === 'include' && !empty($ids)) {
                sort($ids);
                $sortedIndiNepal = $indiNepalIds;
                sort($sortedIndiNepal);
                $scope = ($ids == $sortedIndiNepal) ? 'india_nepal_only' : 'all_countries';
            } elseif ($row->country_scope_type === 'exclude' && !empty($ids)) {
                sort($ids);
                $sortedIndiNepal = $indiNepalIds;
                sort($sortedIndiNepal);
                $scope = ($ids == $sortedIndiNepal) ? 'except_india_nepal' : 'all_countries';
            }

            DB::table('contract_pricings')
                ->where('id', $row->id)
                ->update(['country_scope' => $scope]);
        }
    }
};
