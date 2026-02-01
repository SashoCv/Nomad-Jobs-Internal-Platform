<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

/**
 * Seeds contract types (employment contract types).
 * Table renamed from contract_candidates to contract_types.
 */
class ContractCandidateSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [
            ['name' => 'ЕРПР 1', 'slug' => 'erpr1', 'description' => 'Единен разрешителен за пребиваване и работа - първоначално издаване за 1 година'],
            ['name' => 'ЕРПР 2', 'slug' => 'erpr2', 'description' => 'Единен разрешителен за пребиваване и работа - подновяване за 2 години'],
            ['name' => 'ЕРПР 3', 'slug' => 'erpr3', 'description' => 'Единен разрешителен за пребиваване и работа - подновяване за 3 години'],
            ['name' => '9 месеца', 'slug' => '9months', 'description' => 'Срочен трудов договор за 9 месеца - стандартен договор за чуждестранни работници'],
            ['name' => '90 дни', 'slug' => '90days', 'description' => 'Краткосрочен трудов договор за 90 дни - за временна сезонна работа'],
        ];

        foreach ($contracts as $contract) {
            ContractType::updateOrCreate(['slug' => $contract['slug']], $contract);
        }
    }
}
