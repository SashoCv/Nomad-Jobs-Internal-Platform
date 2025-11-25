<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migrate existing position documents from positions table to position_documents table
        $positionsWithDocuments = DB::table('positions')
            ->whereNotNull('positionPath')
            ->whereNotNull('positionName')
            ->where('positionPath', '!=', '')
            ->get();

        foreach ($positionsWithDocuments as $position) {
            // Check if this document already exists in position_documents
            $exists = DB::table('position_documents')
                ->where('position_id', $position->id)
                ->where('document_name', $position->positionPath)
                ->exists();

            if (!$exists) {
                DB::table('position_documents')->insert([
                    'position_id' => $position->id,
                    'document_name' => $position->positionPath,
                    'created_at' => $position->created_at ?? now(),
                    'updated_at' => $position->updated_at ?? now(),
                ]);
            }
        }

        // Optional: You can choose to keep the old columns for backward compatibility
        // or drop them after confirming the migration was successful
        // Uncomment the lines below if you want to drop the old columns
        /*
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['positionName', 'positionPath']);
        });
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove migrated documents from position_documents table
        // This will only work if you kept the old columns in positions table
        $positionsWithDocuments = DB::table('positions')
            ->whereNotNull('positionPath')
            ->whereNotNull('positionName')
            ->where('positionPath', '!=', '')
            ->get();

        foreach ($positionsWithDocuments as $position) {
            DB::table('position_documents')
                ->where('position_id', $position->id)
                ->where('document_name', $position->positionPath)
                ->delete();
        }
    }
};
