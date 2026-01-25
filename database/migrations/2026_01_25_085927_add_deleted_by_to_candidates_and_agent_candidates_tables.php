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
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
        });

        Schema::table('agent_candidates', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
        });
    }
};
