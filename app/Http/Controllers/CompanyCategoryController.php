<?php

namespace App\Http\Controllers;

use App\Models\CompanyCategory;
use App\Traits\HasRolePermissions;
use App\Models\CompanyFile;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyCategoryController extends Controller
{
    use HasRolePermissions;
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_CREATE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to create company categories.',
            ], 403);
        }

        $request->validate([
            'companyNameCategory' => 'required|string|max:255',
            'company_id' => 'required|integer',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = new CompanyCategory();
        $category->role_id = Auth::user()->role_id;
        $category->companyNameCategory = $request->companyNameCategory;
        $category->company_id = $request->company_id;
        $category->description = $request->description;

        if ($request->has('allowed_roles')) {
            $category->allowed_roles = $request->allowed_roles;
        }

        if ($category->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $category
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 500,
            'message' => 'Failed to create company category.',
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyCategory  $companyCategory
     * @return \Illuminate\Http\Response
     */
      public function destroy(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_DELETE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to delete company categories.',
            ], 403);
        }

        $company_id = $request->company_id;
        $companyCategory_id = $request->company_category_id;

        $companyCategory = CompanyCategory::where('id', '=', $companyCategory_id)->where('company_id', '=', $company_id)->first();

        if (!$companyCategory) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Company category not found.',
            ], 404);
        }

        $files = CompanyFile::where('company_category_id', '=', $companyCategory_id)->where('company_id', '=', $company_id)->get();

        foreach ($files as $file) {
            $filePath = storage_path() . '/app/public/' . $file->filePath;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $file->delete();
        }

        if ($companyCategory->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Company category deleted successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 500,
            'message' => 'Failed to delete company category.',
        ], 500);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_UPDATE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to update company categories.',
            ], 403);
        }

        $request->validate([
            'companyNameCategory' => 'required|string|max:255',
            'role_id' => 'nullable|integer',
            'allowed_roles' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = CompanyCategory::findOrFail($id);
        
        $category->companyNameCategory = $request->companyNameCategory;
        $category->description = $request->description;
        
        if ($request->has('role_id')) {
            $category->role_id = $request->role_id;
        }

        if ($request->has('allowed_roles')) {
            $category->allowed_roles = $request->allowed_roles;
        }

        if ($category->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $category,
                'message' => 'Category updated successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 500,
            'message' => 'Failed to update category.',
        ], 500);
    }
}
