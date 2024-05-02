<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cases')->insert([
            'name' => 'Казуси институции'
        ]);

        DB::table('cases')->insert([
            'name' => 'Казуси фирма'
        ]);

        DB::table('cases')->insert([
            'name' => 'Казуси кандидати'
        ]);
    }
}
