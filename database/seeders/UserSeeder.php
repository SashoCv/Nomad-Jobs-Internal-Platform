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
            'firstName' => 'Sasho',
            'lastName' => 'cvetanoski',
            'email' => 'sasocvetanoski@gmail.com',
            'password' => bcrypt('test1234'),
            'role_id' => 1,
            'company_id' => 1
        ]);
    }
}
