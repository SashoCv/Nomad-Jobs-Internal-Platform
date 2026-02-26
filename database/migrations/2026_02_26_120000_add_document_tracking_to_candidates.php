<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('pcc_received')->default(false)->after('is_qualified');
            $table->boolean('diploma_received')->default(false)->after('pcc_received');
            $table->boolean('poa_received')->default(false)->after('diploma_received');
            $table->boolean('medical_form_received')->default(false)->after('poa_received');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['pcc_received', 'diploma_received', 'poa_received', 'medical_form_received']);
        });
    }
};
