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
        Schema::table('status_for_candidate_from_agents', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('name');
            $table->boolean('show_for_companies')->default(false)->after('order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_for_candidate_from_agents', function (Blueprint $table) {
            $table->dropColumn(['order', 'show_for_companies']);
        });
    }
};
