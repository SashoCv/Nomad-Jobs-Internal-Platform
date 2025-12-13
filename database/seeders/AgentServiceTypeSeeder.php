<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Сума на комисионна'],
            ['name' => 'Подбор на кандидати'],
            ['name' => 'Обработка на документи'],
            ['name' => 'Консултация'],
            ['name' => 'Превод на документи'],
            ['name' => 'Легализация на документи'],
            ['name' => 'Съдействие при регистрация'],
            ['name' => 'Административно съдействие'],
        ];

        foreach ($data as $item) {
            \App\Models\AgentServiceType::firstOrCreate(['name' => $item['name']]);
        }
    }
}
