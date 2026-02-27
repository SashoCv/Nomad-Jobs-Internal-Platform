<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('contractPeriod');
            $table->dropColumn('contractExtensionPeriod');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropColumn('contract_period');
            $table->dropColumn('contract_extension_period');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('contractPeriod')->nullable();
            $table->string('contractExtensionPeriod')->nullable();
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->string('contract_period', 100)->nullable()->after('contract_period_number');
            $table->string('contract_extension_period', 100)->nullable()->after('contract_period');
        });
    }
};
