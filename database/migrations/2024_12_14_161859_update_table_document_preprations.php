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
        Schema::table('migration_document_preparations', function (Blueprint $table) {
            $table->boolean('conditionsMetDeclaration')->default(false);
            $table->boolean('jobDescription')->default(false);
            $table->boolean('employmentContract')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('migration_document_preparations', function (Blueprint $table) {
            //
        });
    }
};
