<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new status "Насрочено за виза" if not exists
        DB::table('statuses')->insertOrIgnore([
            'id' => 20,
            'nameOfStatus' => 'Насрочено за виза',
            'order' => 5,
            'showOnHomePage' => 1,
        ]);

        // Add visa_appointment to calendar_events type ENUM
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

    public function down(): void
    {
        // Remove calendar events with this type first
        DB::table('calendar_events')->where('type', 'visa_appointment')->delete();

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
            'hired'
        ) NOT NULL");

        // Remove the status
        DB::table('statuses')->where('id', 20)->delete();
    }
};
