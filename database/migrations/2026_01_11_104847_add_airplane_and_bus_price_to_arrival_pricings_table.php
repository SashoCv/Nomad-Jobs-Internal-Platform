<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('arrival_pricings', function (Blueprint $table) {
            $table->decimal('airplane_price', 10, 2)->nullable()->after('arrival_id');
            $table->decimal('bus_price', 10, 2)->nullable()->after('airplane_price');
        });

        // Migrate existing price data to airplane_price (assuming old price was airplane)
        DB::statement('UPDATE arrival_pricings SET airplane_price = price WHERE price IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('arrival_pricings', function (Blueprint $table) {
            $table->dropColumn(['airplane_price', 'bus_price']);
        });
    }
};
