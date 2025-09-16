<?php

namespace App\Console\Commands;

use App\Models\CompanyAdress;
use Illuminate\Console\Command;

class AddCityIdForCompaniesAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'city:add-city-id-for-companies-address';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add city ID for companies address';


    public function handle()
    {
        $companiesAddresses = CompanyAdress::all();
        foreach ($companiesAddresses as $address) {
          $cityName = $address->city;
          $city = \App\Models\City::where('name', $cityName)->first();
            if ($city) {
                $address->city_id = $city->id;
                $address->save();
                $this->info("Updated address ID {$address->id} with city ID {$city->id}");
            } else {
                $this->warn("City '{$cityName}' not found for address ID {$address->id}");
            }
        }
    }
}
