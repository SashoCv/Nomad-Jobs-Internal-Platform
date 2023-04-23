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
            'nameOfStatus' => 'Подаден в Миграция',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Получил разрешение',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Подаден в посолство',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Получил виза',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Пристигнал',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'В процедура за ID Card',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Сниман за ID Card',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Получил ID Card',
        ]);
       
        DB::table('statuses')->insert([
            'nameOfStatus' => 'Назначен на работа',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Приключил Договор',
        ]);

        DB::table('statuses')->insert([
            'nameOfStatus' => 'Прекратен договор',
        ]);
    }
}
