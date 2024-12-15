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
        Schema::table('document_preparations', function (Blueprint $table) {
            $table->boolean('authorization')->default(false);
            $table->boolean('residenceDeclaration')->default(false);
            $table->boolean('justificationAuthorization')->default(false);
            $table->boolean('declarationOfForeigners')->default(false);
            $table->boolean('notarialDeed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_preparations', function (Blueprint $table) {
            //
        });
    }
};
