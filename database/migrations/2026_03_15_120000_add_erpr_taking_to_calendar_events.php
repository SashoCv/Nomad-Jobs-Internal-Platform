<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add erpr_taking to calendar_events type ENUM
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
            'hired',
            'visa_appointment',
            'erpr_taking'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Remove calendar events with this type first
        DB::table('calendar_events')->where('type', 'erpr_taking')->delete();

        // Revert ENUM
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
            'hired',
            'visa_appointment'
        ) NOT NULL");
    }
};
