<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyAdress;
use App\Models\File;
use App\Models\User;
use App\Models\UserOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;


class CompanyController extends Controller
{


    public function index()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companies = Company::with('company_addresses')->get();

            return response()->json([
                'status' => 200,
                'data' => $companies
            ]);
        } else if (Auth::user()->role_id == 3) {
            $companies = Company::where('id', '=', Auth::user()->company_id)->get(['id', 'nameOfCompany']);

            return response()->json([
                'status' => 200,
                'data' => $companies
            ]);
        } else if (Auth::user()->role_id == 5) {
            $userOwners = UserOwner::where('user_id', '=', Auth::user()->id)->get();
            $userOwnersArray = [];
            foreach ($userOwners as $userOwner) {
                array_push($userOwnersArray, $userOwner->company_id);
            }
            $companies = Company::with('company_addresses')->whereIn('id', $userOwnersArray)->get();


            return response()->json([
                'status' => 200,
                'data' => $companies
            ]);
        }
    }


    public function validateCompanyByEik($eik)
    {
        $allCompanies = Company::all();
        $company = $allCompanies->where('EIK', '=', $eik)->first();

        if($company){
            return true;
        } else {
            return false;
        }
    }
    public function store(Request $request)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $eik = $request->EIK;

            if($this->validateCompanyByEik($eik)){
                throw new \Exception('Company with this EIK already exists!');
            }

            if($request->commissionRate == "null"){
                $commissionRate = null;
            } else {
                $commissionRate = $request->commissionRate;
            }

            $company = new Company();

            if ($request->hasFile('companyLogo')) {
                Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
                $name = Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
                $company->logoPath = $name;
                $company->logoName = $request->file('companyLogo')->getClientOriginalName();
            }

            if ($request->hasFile('companyStamp')) {
                Storage::disk('public')->put('companyImages', $request->file('companyStamp'));
                $name = Storage::disk('public')->put('companyImages', $request->file('companyStamp'));
                $company->stampPath = $name;
                $company->stampName = $request->file('companyStamp')->getClientOriginalName();
            }

            $company->nameOfCompany = $request->nameOfCompany;
            $company->address = $request->address;
            $company->email = $request->email;
            $company->website = $request->website;
            $company->phoneNumber = $request->phoneNumber;
            $company->EIK = $request->EIK;
            $company->contactPerson = $request->contactPerson;
            $company->EGN = $request->EGN;
            $company->dateBornDirector = $request->dateBornDirector;
            $company->companyCity = $request->companyCity;
            $company->industry_id = $request->industry_id;
            $company->foreignersLC12 = $request->foreignersLC12;
            $company->description = $request->description;
            $company->nameOfContactPerson = $request->nameOfContactPerson;
            $company->phoneOfContactPerson = $request->phoneOfContactPerson;
            $company->director_idCard = $request->director_idCard;
            $company->director_date_of_issue_idCard = $request->director_date_of_issue_idCard;
            $company->commissionRate = $commissionRate;



            if ($request->employedByMonths) {
                $employedByMonths = json_decode(json_encode($request->employedByMonths));
            }

            $company->employedByMonths = $employedByMonths ?? Null;

            $company_addresses = json_decode($request->company_addresses, true);
            Log::info('company_addresses:', [$company_addresses]);
            Log::info('request_company_addresses:', [$request->company_addresses]);

            if ($company->save()) {

                if ($company_addresses) {
                    foreach ($company_addresses as $address) {
                        $companyAddress = new CompanyAdress();
                        $companyAddress->company_id = $company->id;
                        $companyAddress->address = $address['address'];
                        $companyAddress->city = $address['city'];
                        $companyAddress->state = $address['state'];
                        $companyAddress->zip_code = $address['zip_code'];
                        $companyAddress->save();
                    }
                }
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $company

                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 403,
                'data' => []
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $company = Company::with(['industry','company_addresses'])->where('id', '=', $id)->first();
        } else if(Auth::user()->role_id == 3) {
            $company = Company::with(['industry','company_addresses'])->where('id', '=', Auth::user()->company_id)->first();
        } else if(Auth::user()->role_id == 5) {
            $userOwners = UserOwner::where('user_id', '=', Auth::user()->id)->get();
            $userOwnersArray = [];
            foreach($userOwners as $userOwner) {
                array_push($userOwnersArray, $userOwner->company_id);
            }
            $company = Company::with(['industry','company_addresses'])->whereIn('id', $userOwnersArray)->where('id', '=', $id)->first();
        }

        if($company){
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $company
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ],500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {


            if ($request->addressOne === 'null') {
                $addressOne = Null;
            } else {
                $addressOne = $request->addressOne;
            }

            if ($request->addressTwo === 'null') {
                $addressTwo = Null;
            } else {
                $addressTwo = $request->addressTwo;
            }

            if ($request->addressThree === 'null') {
                $addressThree = Null;
            } else {
                $addressThree = $request->addressThree;
            }

            if ($request->employedByMonths === 'null') {
                $employedByMonths = Null;
            } else {
                $employedByMonths = json_decode(json_encode($request->employedByMonths));
            }

            if ($request->description === 'null') {
                $description = Null;
            } else {
                $description = $request->description;
            }

            $company = Company::where('id', '=', $id)->first();

            if ($request->hasFile('companyLogo')) {
                Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
                $name = Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
                $company->logoPath = $name;
                $company->logoName = $request->file('companyLogo')->getClientOriginalName();
            }

            if ($request->hasFile('companyStamp')) {
                Storage::disk('public')->put('companyImages', $request->file('companyStamp'));
                $name = Storage::disk('public')->put('companyImages', $request->file('companyStamp'));
                $company->stampPath = $name;
                $company->stampName = $request->file('companyStamp')->getClientOriginalName();
            }

            if($request->commissionRate == 'null'){
                $commissionRate = null;
            } else {
                $commissionRate = $request->commissionRate;
            }

            $company->nameOfCompany = $request->nameOfCompany;
            $company->address = $request->address;
            $company->email = $request->email;
            $company->website = $request->website;
            $company->phoneNumber = $request->phoneNumber;
            $company->EIK = $request->EIK;
            $company->contactPerson = $request->contactPerson;
            $company->EGN = $request->EGN;
            $company->dateBornDirector = $request->dateBornDirector;
            $company->companyCity = $request->companyCity;
            $company->industry_id = $request->industry_id;
            $company->foreignersLC12 = $request->foreignersLC12;
            $company->description = $request->description;
            $company->nameOfContactPerson = $request->nameOfContactPerson;
            $company->phoneOfContactPerson = $request->phoneOfContactPerson;
            $company->director_idCard = $request->director_idCard;
            $company->director_date_of_issue_idCard = $request->director_date_of_issue_idCard;
            $company->commissionRate = $commissionRate;

            $company_addresses = json_decode($request->company_addresses, true);


            if ($company->save()) {
                if ($company_addresses) {
                    $companyAddresses = CompanyAdress::where('company_id', '=', $company->id)->get();
                    foreach ($companyAddresses as $companyAddress) {
                        $companyAddress->delete();
                    }
                    foreach ($company_addresses as $address) {
                        $companyAddress = new CompanyAdress();
                        $companyAddress->company_id = $company->id;
                        $companyAddress->address = $address['address'];
                        $companyAddress->city = $address['city'];
                        $companyAddress->state = $address['state'];
                        $companyAddress->zip_code = $address['zip_code'];
                        $companyAddress->save();
                    }
                }
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $company,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => [],
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            try {
                $company = Company::findOrFail($id);
                $company->delete();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => []
                ]);
            } catch (\Exception $e) {
                Log::info('Error deleting company:', [$e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => []
                ]);
            }
        }
    }
}
