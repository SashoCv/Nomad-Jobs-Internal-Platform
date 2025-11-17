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
            $table->unsignedBigInteger('administrative_position_id')->nullable()->after('position_id');
            $table->string('real_position')->nullable()->after('administrative_position_id');
            $table->unsignedBigInteger('country_id')->nullable()->after('countryOfOrigin');

            $table->foreign('administrative_position_id', 'company_jobs_admin_pos_fk')
                  ->references('id')
                  ->on('administrative_positions')
                  ->onDelete('set null');

            $table->foreign('country_id', 'company_jobs_country_fk')
                  ->references('id')
                  ->on('countries')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropForeign('company_jobs_admin_pos_fk');
            $table->dropColumn('administrative_position_id');
            $table->dropColumn('real_position');
            $table->dropForeign('company_jobs_country_fk');
            $table->dropColumn('country_id');
        });
    }
};
