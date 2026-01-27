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
        // Add new status-based types to calendar_events ENUM
        DB::statement("ALTER TABLE calendar_events MODIFY COLUMN type ENUM(
            'interview',
            'arrival',
            'contract_expiry',
            'insurance_expiry',
            'visa_expiry',
            'passport_expiry',
            'received_visa',
            'erpr_procedure',
            'erpr_letter',
            'erpr_photo',
            'hired'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new types (only if no records exist with those types)
        DB::statement("ALTER TABLE calendar_events MODIFY COLUMN type ENUM(
            'interview',
            'arrival',
            'contract_expiry',
            'insurance_expiry',
            'visa_expiry',
            'passport_expiry'
        ) NOT NULL");
    }
};
