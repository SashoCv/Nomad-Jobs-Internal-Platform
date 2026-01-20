<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('categories')
            ->where('nameOfCategory', 'Documents For Arrival Candidates')
            ->update(['nameOfCategory' => 'Arrival Documents / Документи за пристигане']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('categories')
            ->where('nameOfCategory', 'Arrival Documents / Документи за пристигане')
            ->update(['nameOfCategory' => 'Documents For Arrival Candidates']);
    }
};
