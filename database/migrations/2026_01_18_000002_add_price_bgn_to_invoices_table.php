<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Conversion rate: 1 EUR = 1.95583 BGN
     */
    private const BGN_TO_EUR_RATE = 1.95583;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add price_bgn column for historical BGN prices
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('price_bgn', 10, 2)
                ->nullable()
                ->after('price')
                ->comment('Historical price in BGN (before EUR conversion)');
        });

        // Step 2: Copy existing price values to price_bgn (these are currently in BGN)
        DB::statement('UPDATE invoices SET price_bgn = price WHERE price IS NOT NULL');

        // Step 3: Convert price from BGN to EUR
        DB::statement('UPDATE invoices SET price = ROUND(price / ' . self::BGN_TO_EUR_RATE . ', 2) WHERE price IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Restore price from price_bgn (convert back to BGN)
        DB::statement('UPDATE invoices SET price = price_bgn WHERE price_bgn IS NOT NULL');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('price_bgn');
        });
    }
};
