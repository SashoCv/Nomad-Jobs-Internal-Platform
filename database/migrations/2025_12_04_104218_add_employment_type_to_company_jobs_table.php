<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->enum('employment_type', ['annual', 'seasonal'])
                ->nullable()
                ->after('contract_type')
                ->comment('Annual (годишна) or Seasonal (сезонна) employment');
        });

        // Update existing records based on contract_type
        DB::statement("
            UPDATE company_jobs
            SET employment_type = CASE
                WHEN contract_type LIKE 'ЕРПР%' OR contract_type = 'indefinite' OR contract_type = '9months' THEN 'annual'
                WHEN contract_type = '90 дни' THEN 'seasonal'
                ELSE NULL
            END
            WHERE contract_type IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
};
