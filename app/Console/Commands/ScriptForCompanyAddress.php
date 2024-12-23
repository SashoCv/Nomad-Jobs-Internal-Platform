<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyAdress;
use Illuminate\Console\Command;

class ScriptForCompanyAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'script:company-address';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Script for company address';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating company address table...');
        $allCompanies = Company::all();
        foreach ($allCompanies as $company) {
            $companyAddressOne = $company->addressOne;
            $companyAddressTwo = $company->addressTwo;
            $companyAddressThree = $company->addressThree;
            $companyCity = $company->companyCity;

            $address = [];
            if ($companyAddressOne) {
                $address[] = $companyAddressOne;
            }
            if ($companyAddressTwo) {
                $address[] = $companyAddressTwo;
            }
            if ($companyAddressThree) {
                $address[] = $companyAddressThree;
            }

            foreach ($address as $key => $value) {
                $companyAddress = new CompanyAdress();
                $companyAddress->company_id = $company->id;
                $companyAddress->address = $value;
                $companyAddress->city = $companyCity;
                $companyAddress->state = $company->state ?? null;
                $companyAddress->zip_code = $company->zip_code ?? null;
                $companyAddress->save();
            }
        }
        $this->info('Company address table created successfully!');
    }
}
