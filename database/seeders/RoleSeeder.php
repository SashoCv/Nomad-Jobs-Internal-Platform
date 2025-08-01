<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            'roleName' => 'Генерален Мениџер/Администратор',
        ]);
        
        DB::table('roles')->insert([
            'roleName' => 'Мениџер',
        ]);
        
        DB::table('roles')->insert([
            'roleName' => 'company user',
        ]);
    }
}
