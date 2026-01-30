<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->boolean('is_extension')->default(false)->after('is_active');
        });
    }
};
