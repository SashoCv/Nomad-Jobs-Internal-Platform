<?php

namespace Database\Seeders;

use App\Models\StatusArrival;
use Illuminate\Database\Seeder;

class StatusArrivalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            ['statusName' => 'Пристигнал'],
            ['statusName' => 'Миграция'],
            ['statusName' => 'Процедура за ЕРПР'],
            ['statusName' => 'Процедура за Писмо'],
            ['statusName' => 'Снимка за ЕРПР'],
            ['statusName' => 'Получаване на ЕРПР'],
        ];

        StatusArrival::insert($statuses);
    }
}
