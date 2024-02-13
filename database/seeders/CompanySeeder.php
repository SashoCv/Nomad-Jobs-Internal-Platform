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
            'address' => 'Sofia, Bulgaria, Blvd. Bulgaria No. 51V, ENT. S, apt. 18',
            'email' => 'office@nomadpartners.bg',
            'website' => 'https://nomadpartners.bg/',
            'phoneNumber' => '+359 (0)89 961 7910',
            'EIK' => 'KJS123K',
            'contactPerson' => 'Elena',
            'logoPath' => 'test',
            'logoName' => 'test',
        ]);
    }
}
