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
        Schema::table('item_invoices', function (Blueprint $table) {
            $table->float('percentage')->nullable();
            $table->float('amount')->nullable();
            $table->foreignId('items_for_invoices_id')->constrained('items_for_invoices')->onDelete('cascade');

            $table->dropColumn('item_name');
            $table->dropColumn('quantity');
            $table->dropColumn('unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_invoices', function (Blueprint $table) {
            $table->dropColumn('percentage');
            $table->dropColumn('amount');
            $table->dropForeign(['items_for_invoices_id']);
            $table->dropColumn('items_for_invoices_id');

            $table->string('item_name');
            $table->integer('quantity');
            $table->string('unit');
        });
    }
};
