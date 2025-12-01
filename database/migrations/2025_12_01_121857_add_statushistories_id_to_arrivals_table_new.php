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
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'statushistories_id')) {
                $table->foreignId('statushistories_id')->nullable()->constrained('statushistories')->onDelete('cascade')->after('candidate_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'statushistories_id')) {
                $table->dropForeign(['statushistories_id']);
                $table->dropColumn('statushistories_id');
            }
        });
    }
};
