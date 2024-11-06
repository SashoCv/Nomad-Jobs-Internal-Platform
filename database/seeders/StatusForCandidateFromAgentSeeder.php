<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusForCandidateFromAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Добавен'],
            ['name' => 'За интервю'],
            ['name' => 'Одобрен'],
            ['name' => 'Неподходящ'],
            ['name' => 'Резерва'],
            ['name' => 'Отказан'],
        ];

        foreach ($data as $row) {
            \App\Models\StatusForCandidateFromAgent::create($row);
        }
    }
}
