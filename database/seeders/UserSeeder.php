<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'firstName' => 'Милен',
            'lastName' => 'Володиев',
            'email' => 'milenvolodiev@nomadpartners.com',
            'password' => bcrypt('nomad123$'),
            'role_id' => 1,
            'company_id' => null
        ]);
    }
}
