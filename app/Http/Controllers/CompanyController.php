<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::all();

        $headers = ['Access-Control-Allow-Origin' => '"*"', 'Content-Type' => 'application/json; charset=utf-8'];
        return response()->json($companies, 200, $headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $company = new Company();

        if ($request->hasFile('companyLogo')) {
            Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
            $name = Storage::disk('public')->put('companyImages', $request->file('companyLogo'));
            $company->logoPath = $name;
            $company->logoName = $request->file('companyLogo')->getClientOriginalName();
        }

        $company->nameOfCompany = $request->nameOfCompany;
        $company->address = $request->address;
        $company->email = $request->email;
        $company->website = $request->website;
        $company->phoneNumber = $request->phoneNumber;


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
    public function show(Company $company)
    {
        //
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
    public function update(Request $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }
}
