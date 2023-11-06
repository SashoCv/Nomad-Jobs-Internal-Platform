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
        Schema::table('company_files', function (Blueprint $table) {
            $table->foreignId('company_category_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_files', function (Blueprint $table) {
            $table->dropForeign('company_category_id');
            $table->dropColumn('company_category_id');
            
        });
    }
};
