<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContractCandidate;

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
                'slug' => 'indefinite', 
                'description' => 'Единен разрешителен за пребиваване и работа - първоначално издаване за 3 години'
            ],
            [
                'name' => 'ЕРПР 2',
                'slug' => 'indefinite', 
                'description' => 'Единен разрешителен за пребиваване и работа - подновяване за 5 години'
            ],
            [
                'name' => 'ЕРПР 3',
                'slug' => 'indefinite', 
                'description' => 'Единен разрешителен за пребиваване и работа - дългосрочно пребиваване, безсрочен'
            ]
        ];

        foreach ($contractTypes as $contractType) {
            ContractCandidate::updateOrCreate(
                ['name' => $contractType['name']],
                $contractType
            );
        }
    }
}