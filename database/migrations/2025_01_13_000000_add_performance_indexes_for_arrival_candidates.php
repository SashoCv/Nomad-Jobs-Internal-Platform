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
        Schema::table('statushistories', function (Blueprint $table) {
            // Composite index for candidate_id + created_at for faster latest status lookup
            $table->index(['candidate_id', 'created_at'], 'idx_candidate_created_at');
            
            // Composite index for status_id + statusDate for filtering and sorting
            $table->index(['status_id', 'statusDate'], 'idx_status_date');
            
            // Composite index for candidate_id + status_id for filtering by candidate and status
            $table->index(['candidate_id', 'status_id'], 'idx_candidate_status');
        });

        Schema::table('files', function (Blueprint $table) {
            // Index for candidate_id to speed up file existence checks
            $table->index('candidate_id', 'idx_files_candidate_id');
        });

        Schema::table('statuses', function (Blueprint $table) {
            // Index for showOnHomePage for filtering
            $table->index('showOnHomePage', 'idx_show_on_homepage');
            
            // Index for order for status ordering logic
            $table->index('order', 'idx_status_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('statushistories', function (Blueprint $table) {
            $table->dropIndex('idx_candidate_created_at');
            $table->dropIndex('idx_status_date');
            $table->dropIndex('idx_candidate_status');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('idx_files_candidate_id');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->dropIndex('idx_show_on_homepage');
            $table->dropIndex('idx_status_order');
        });
    }
};