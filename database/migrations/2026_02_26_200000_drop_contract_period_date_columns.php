<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('contractPeriodDate');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropColumn('contract_period_date');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->timestamp('contractPeriodDate')->nullable()->after('endContractDate');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->date('contract_period_date')->nullable()->after('end_contract_date');
        });
    }
};
