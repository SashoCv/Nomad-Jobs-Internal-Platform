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
<<<<<<< HEAD:database/migrations/2024_02_20_160147_add_soft_delete_column.php
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->softDeletes();
=======
        Schema::table('candidates', function (Blueprint $table) {
        $table->foreignId('position_id')->constrained();
>>>>>>> 4430c892091923b9624eb38cd41c4685d0ae29cf:database/migrations/2023_06_21_161945_update_table_candidates_position_id.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
<<<<<<< HEAD:database/migrations/2024_02_20_160147_add_soft_delete_column.php
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropSoftDeletes();
=======
        Schema::table('candidates', function (Blueprint $table) {
                 $table->dropForeign('position_id');
            $table->dropColumn('position_id');
>>>>>>> 4430c892091923b9624eb38cd41c4685d0ae29cf:database/migrations/2023_06_21_161945_update_table_candidates_position_id.php
        });
    }
};
