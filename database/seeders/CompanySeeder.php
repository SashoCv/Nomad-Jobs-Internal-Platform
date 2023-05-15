<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->insert([
            'nameOfCompany' => 'Nomad',
            'address' => 'test address',
            'email' => 'company@gmail.com',
            'website' => 'test.com.mk',
            'phoneNumber' => 'test',
            'logoPath' => 'test',
            'logoName' => 'test',
            'EIK' => 'test',
            'contactPerson' => 'test'
        ]);
    }
}
