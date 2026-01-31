<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContractType;

class UpdateContractCandidatesDescriptions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $contractTypes = [
            [
                'name' => '90 дни',
                'slug' => '90days',
                'description' => 'Краткосрочен трудов договор за 90 дни - за временна сезонна работа'
            ],
            [
                'name' => '9 месеца',
                'slug' => '9months',
                'description' => 'Срочен трудов договор за 9 месеца - стандартен договор за чуждестранни работници'
            ],
            [
                'name' => 'ЕРПР 1',
                'slug' => 'erpr1',
                'description' => 'Единен разрешителен за пребиваване и работа - първоначално издаване за 1 година'
            ],
            [
                'name' => 'ЕРПР 2',
                'slug' => 'erpr2',
                'description' => 'Единен разрешителен за пребиваване и работа - подновяване за 2 години'
            ],
            [
                'name' => 'ЕРПР 3',
                'slug' => 'erpr3',
                'description' => 'Единен разрешителен за пребиваване и работа - подновяване за 3 години'
            ]
        ];

        foreach ($contractTypes as $contractType) {
            ContractType::updateOrCreate(
                ['name' => $contractType['name']],
                $contractType
            );
        }
    }
}