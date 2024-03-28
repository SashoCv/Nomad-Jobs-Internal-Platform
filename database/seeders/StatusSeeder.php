<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('statuses')->insert([
            'nameOfStatus' => 'Отказ от Миграция',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Отказ от кандидат',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Отказ от компаия',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Изпратини документи за виза Д',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Изготвени документи за Миграция',
        ]);
    }
}
