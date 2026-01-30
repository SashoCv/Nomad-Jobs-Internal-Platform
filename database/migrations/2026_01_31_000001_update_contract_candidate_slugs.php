<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contract_candidates')
            ->where('name', 'ЕРПР 1')
            ->update(['slug' => 'erpr1']);

        DB::table('contract_candidates')
            ->where('name', 'ЕРПР 2')
            ->update(['slug' => 'erpr2']);

        DB::table('contract_candidates')
            ->where('name', 'ЕРПР 3')
            ->update(['slug' => 'erpr3']);
    }
};
