<?php

namespace App\Http\Controllers;

use App\Models\CompanyCategory;
use App\Traits\HasRolePermissions;
use App\Models\CompanyFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyCategoryController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        if ($this->isStaff()) {

            $category = new CompanyCategory();

            $category->role_id = Auth::user()->role_id;
            $category->companyNameCategory = $request->companyNameCategory;
            $category->company_id = $request->company_id;

            if ($category->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $category
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => [],
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyCategory  $companyCategory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyCategory  $companyCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyCategory $companyCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyCategory  $companyCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyCategory $companyCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyCategory  $companyCategory
     * @return \Illuminate\Http\Response
     */
      public function destroy(Request $request)
    {
        $company_id = $request->company_id;
        $companyCategory_id = $request->company_category_id;

        $companyCategory = CompanyCategory::where('id', '=', $companyCategory_id)->where('company_id', '=', $company_id)->first();

        $files = CompanyFile::where('company_category_id', '=', $companyCategory_id)->where('company_id', '=', $company_id)->get();

        foreach ($files as $file) {
            unlink(storage_path() . '/app/public/' . $file->filePath);
            $file->delete();
        }

        if ($companyCategory->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your category has been deleted!',
            ]);
        }
    }
}
