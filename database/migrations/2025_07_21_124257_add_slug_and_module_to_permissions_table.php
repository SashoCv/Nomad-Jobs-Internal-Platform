<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('module')->nullable()->after('slug');
        });
        
        // Clear existing permissions and let seeder recreate them
        DB::table('role_permissions')->delete();
        DB::table('permissions')->delete();
        
        // Now add the unique constraint
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('slug')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['slug', 'module']);
        });
    }
};