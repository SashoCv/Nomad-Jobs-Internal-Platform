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
        // Move all files from position_documents to position_files
        $documents = DB::table('position_documents')->get();

        foreach ($documents as $doc) {
            // Check if position exists
            $positionExists = DB::table('positions')->where('id', $doc->position_id)->exists();

            if (!$positionExists) {
                // Skip if position doesn't exist
                continue;
            }

            // Extract file name from path
            $fileName = basename($doc->document_name);

            DB::table('position_files')->insert([
                'position_id' => $doc->position_id,
                'file_name' => $fileName,
                'file_path' => $doc->document_name,
                'created_at' => $doc->created_at ?? now(),
                'updated_at' => $doc->updated_at ?? now(),
            ]);
        }

        // Clear position_documents table (will be used for document names only)
        DB::table('position_documents')->truncate();

        // Update position_documents structure to store only document names
        Schema::table('position_documents', function (Blueprint $table) {
            $table->dropColumn('document_name');
            $table->string('name')->after('position_id'); // Document name/title (e.g., "Медицинско уверение")
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Restore position_documents structure
        Schema::table('position_documents', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('document_name')->after('position_id');
        });

        // Move files back from position_files to position_documents
        $files = DB::table('position_files')->get();

        foreach ($files as $file) {
            DB::table('position_documents')->insert([
                'position_id' => $file->position_id,
                'document_name' => $file->file_path,
                'created_at' => $file->created_at ?? now(),
                'updated_at' => $file->updated_at ?? now(),
            ]);
        }
    }
};
