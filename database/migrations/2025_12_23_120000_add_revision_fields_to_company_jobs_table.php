<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the enum to include 'revision_requested'
        DB::statement("ALTER TABLE company_jobs MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'filled', 'rejected', 'revision_requested') DEFAULT 'pending'");

        Schema::table('company_jobs', function (Blueprint $table) {
            // JSON field to store proposed revision data
            $table->json('pending_revision')->nullable()->after('status');

            // Who requested the revision
            $table->unsignedBigInteger('revision_requested_by')->nullable()->after('pending_revision');

            // When the revision was requested
            $table->timestamp('revision_requested_at')->nullable()->after('revision_requested_by');

            $table->foreign('revision_requested_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_jobs', function (Blueprint $table) {
            $table->dropForeign(['revision_requested_by']);
            $table->dropColumn(['pending_revision', 'revision_requested_by', 'revision_requested_at']);
        });

        // Revert the enum (any revision_requested jobs become pending)
        DB::statement("UPDATE company_jobs SET status = 'pending' WHERE status = 'revision_requested'");
        DB::statement("ALTER TABLE company_jobs MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'filled', 'rejected') DEFAULT 'pending'");
    }
};
