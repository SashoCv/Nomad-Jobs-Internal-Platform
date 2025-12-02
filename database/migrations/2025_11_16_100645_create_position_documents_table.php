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
        // Only create if it doesn't exist
        if (!Schema::hasTable('position_documents')) {
            Schema::create('position_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('position_id');
                $table->string('document_name');
                $table->timestamps();

                $table->foreign('position_id', 'position_docs_fk')
                      ->references('id')
                      ->on('positions')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_documents');
    }
};
