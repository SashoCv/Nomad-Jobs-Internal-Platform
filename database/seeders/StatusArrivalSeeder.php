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
            [
                'statusName' => 'Писмо за ЕРПР',
                'order_statuses' => 9,
            ],
        ];

        StatusArrival::insert($statuses);
    }
}
