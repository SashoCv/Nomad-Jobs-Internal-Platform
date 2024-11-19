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
            ['statusName' => 'Получил за виза'],
            ['statusName' => 'Очаква се'],
            ['statusName' => 'Назначен на работа']
        ];

        StatusArrival::insert($statuses);
    }
}
