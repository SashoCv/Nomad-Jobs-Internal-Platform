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
        Schema::table('files', function (Blueprint $table) {
            $table->string('filePath1');
            $table->string('fileName1');
            $table->string('filePath2');
            $table->string('fileName2');
            $table->string('filePath3');
            $table->string('fileName3');
            $table->string('filePath4')->nullable();
            $table->string('fileName4')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('filePath2');
            $table->dropColumn('fileName2');
            $table->dropColumn('filePath3');
            $table->dropColumn('fileName3');
            $table->dropColumn('filePath4');
            $table->dropColumn('fileName4');
            $table->dropColumn('filePath5');
            $table->dropColumn('fileName5');
        });
    }
};
