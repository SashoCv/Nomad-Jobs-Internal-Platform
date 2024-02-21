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
<<<<<<< HEAD:database/migrations/2024_02_21_123947_update_table_companies_jobs_add_job_description_field.php
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->text('job_description')->after('job_title')->nullable();
=======
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('favorite');
>>>>>>> 4430c892091923b9624eb38cd41c4685d0ae29cf:database/migrations/2023_04_23_100451_update_table_candidates_add_favorite_candindate_column.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
<<<<<<< HEAD:database/migrations/2024_02_21_123947_update_table_companies_jobs_add_job_description_field.php
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropColumn('job_description');
=======
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('favorite');
>>>>>>> 4430c892091923b9624eb38cd41c4685d0ae29cf:database/migrations/2023_04_23_100451_update_table_candidates_add_favorite_candindate_column.php
        });
    }
};
