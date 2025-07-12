<?php

namespace Database\Seeders;

use App\Models\ContractCandidate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContaractCandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $contracts = [
            ['name' => 'ЕРПР 1', 'slug' => 'indefinite'],
            ['name' => 'ЕРПР 2', 'slug' => 'indefinite'],
            ['name' => 'ЕРПР 3', 'slug' => 'indefinite'],
            ['name' => '9 месеци', 'slug' => '9months'],
            ['name' => '90 дена', 'slug' => '90days'],
        ];

        foreach ($contracts as $contract) {
            ContractCandidate::create($contract);
        }
    }
}
