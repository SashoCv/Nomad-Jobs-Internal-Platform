<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Company;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;


class CompanyController extends Controller
{


    public function index()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $companies = Company::get(['id', 'nameOfCompany']);

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
        } else {
            return response()->json([
                'status' => 500,
                'data' => []
            ]);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

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
            $company->companyCity = $request->companyCity;
            $company->EGN = $request->EGN;
            $company->dateBornDirector = $request->dateBornDirector;
            $company->addressOne = $request->addressOne;
            $company->addressTwo = $request->addressTwo;
            $company->addressThree = $request->addressThree;
            $company->industry_id = $request->industry_id;
            $company->foreignersLC12 = $request->foreignersLC12;
            $company->description = $request->description;


            if ($request->employedByMonths) {
                $employedByMonths = json_decode(json_encode($request->employedByMonths));
            }

            $company->employedByMonths = $employedByMonths ?? Null;



            if ($company->save()) {
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
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $candidates = Candidate::with(['status', 'type'])->where('company_id', '=', $id)
        //     ->where('type_id', '=', 1)
        //     ->get();

        // $workers = Candidate::with(['status', 'type'])->where('company_id', '=', $id)
        //     ->where('type_id', '=', 2)
        //     ->get();

        $company = Company::with('industry')->where('id', '=', $id)->first();


        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $company
        ]);
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
     * @return \Illuminate\Http\Response
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

            $company->nameOfCompany = $request->nameOfCompany;
            $company->address = $request->address;
            $company->email = $request->email;
            $company->website = $request->website;
            $company->phoneNumber = $request->phoneNumber;
            $company->EIK = $request->EIK;
            $company->contactPerson = $request->contactPerson;
            $company->companyCity = $request->companyCity;
            $company->EGN = $request->EGN;
            $company->dateBornDirector = $request->dateBornDirector;
            $company->addressOne = $addressOne;
            $company->addressTwo = $addressTwo;
            $company->addressThree = $addressThree;
            $company->industry_id = $request->industry_id;
            $company->foreignersLC12 = $request->foreignersLC12;
            $company->employedByMonths = $employedByMonths;
            $company->description = $description;


            if ($company->save()) {
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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $companyDelete = Company::findOrFail($id);
            $candidates = Candidate::where('company_id', '=', $id)->get();

            foreach ($candidates as $candidate) {

                $files = File::where('candidate_id', '=', $candidate->id)->get();
                foreach ($files as $file) {
                    $file->delete();
                }

                $categories = Category::where('candidate_id', '=', $candidate->id)->get();

                foreach ($categories as $category) {
                    $category->delete();
                }

                $candidate->delete();
            }

            $users = User::where('company_id', '=', $id)->get();

            foreach ($users as $user) {
                $user->delete();
            }

            if ($companyDelete->delete()) {
                // unlink(storage_path() . '/app/public/' . $companyDelete->logoPath);

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Proof! Your Company has been deleted!',
                ]);
            }
        }
    }
}
