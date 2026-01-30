<?php

namespace Database\Seeders;

use App\Models\ContractCandidate;
use Illuminate\Database\Seeder;

class ContractCandidateSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [
            ['name' => 'ЕРПР 1', 'slug' => 'erpr1'],
            ['name' => 'ЕРПР 2', 'slug' => 'erpr2'],
            ['name' => 'ЕРПР 3', 'slug' => 'erpr3'],
            ['name' => '9 месеца', 'slug' => '9months'],
            ['name' => '90 дни', 'slug' => '90days'],
        ];

        foreach ($contracts as $contract) {
            ContractCandidate::create($contract);
        }
    }
}
