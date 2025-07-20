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
        // Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create role_permissions pivot table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // Add new roles
        DB::table('roles')->insert([
            ['roleName' => 'Офис'],
            ['roleName' => 'HR/Човешки ресурси'], 
            ['roleName' => 'Офис Мениџер'],
            ['roleName' => 'Рекрутери'],
            ['roleName' => 'Финанси']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        
        // Remove the new roles
        DB::table('roles')->whereIn('roleName', [
            'Офис',
            'HR/Човешки ресурси',
            'Офис Мениџер', 
            'Рекрутери',
            'Финанси'
        ])->delete();
    }
};
