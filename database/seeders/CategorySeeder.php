<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories')->insert([
            'nameOfCategory' => 'Migration Documents',
            'role_id' => 1,
            'candidate_id' => null
        ]);

        DB::table('categories')->insert([
            'nameOfCategory' => 'Visa Documents',
            'role_id' => 2,
            'candidate_id' => null
        ]);

        DB::table('categories')->insert([
            'nameOfCategory' => 'Embassy Documents',
            'role_id' => 1,
            'candidate_id' => null
        ]);

        DB::table('categories')->insert([
            'nameOfCategory' => 'Public Documents',
            'role_id' => 3,
            'candidate_id' => null
        ]);
    }
}
