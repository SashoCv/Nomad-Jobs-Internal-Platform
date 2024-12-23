<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemsForInvoicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Комисионна първа вноско',
            ],
            [
                'name' => 'Комисионна втора вноска',
            ],
            [
                'name' => 'Медицинска застраховка',
            ],
            [
                'name' => 'Транспорт',
            ],
            [
                'name' => 'Такса за Дирекция миграция',
            ],
        ];

        foreach ($data as $item) {
            \App\Models\ItemsForInvoices::create($item);
        }
    }
}
