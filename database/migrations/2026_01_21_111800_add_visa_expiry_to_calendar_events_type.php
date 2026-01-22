<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the ENUM to include the new visa_expiry type
        DB::statement("ALTER TABLE calendar_events MODIFY COLUMN type ENUM('interview', 'arrival', 'contract_expiry', 'insurance_expiry', 'visa_expiry') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove visa_expiry from the ENUM (only if no records exist with that type)
        DB::statement("ALTER TABLE calendar_events MODIFY COLUMN type ENUM('interview', 'arrival', 'contract_expiry', 'insurance_expiry') NOT NULL");
    }
};
