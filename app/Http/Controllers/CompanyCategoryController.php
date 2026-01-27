<?php

namespace App\Http\Controllers;

use App\Models\CompanyCategory;
use App\Models\CompanyFile;
use App\Models\Permission;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyCategoryController extends Controller
{
    use HasRolePermissions;

    public function store(Request $request): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_CREATE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to create company categories.',
            ], 403);
        }

        $request->validate([
            'companyNameCategory' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visible_to_roles' => ['nullable', 'array'],
            'visible_to_roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $category = new CompanyCategory();
        $category->companyNameCategory = $request->companyNameCategory;
        $category->company_id = $request->company_id;
        $category->description = $request->description;

        if (!$category->save()) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to create company category.',
            ], 500);
        }

        $canManageVisibility = $this->isStaff() || $this->checkPermission(Permission::DOCUMENTS_CATEGORIES_MANAGE_VISIBILITY);

        if ($canManageVisibility && $request->has('visible_to_roles')) {
            $category->visibleToRoles()->sync($request->visible_to_roles);
        } else {
            $category->visibleToRoles()->sync([Auth::user()->role_id]);
        }

        $category->load('visibleToRoles');

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_UPDATE) && !$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to update company categories.',
            ], 403);
        }

        $category = CompanyCategory::findOrFail($id);

        if (!$this->isStaff() && !$category->isVisibleToRole(Auth::user()->role_id)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized to edit this category.',
            ], 403);
        }

        $request->validate([
            'companyNameCategory' => ['required', 'string', 'max:255'],
            'visible_to_roles' => ['nullable', 'array'],
            'visible_to_roles.*' => ['integer', 'exists:roles,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->companyNameCategory = $request->companyNameCategory;
        $category->description = $request->description;

        $canManageVisibility = $this->isStaff() || $this->checkPermission(Permission::DOCUMENTS_CATEGORIES_MANAGE_VISIBILITY);

        if ($canManageVisibility && $request->has('visible_to_roles')) {
            $category->visibleToRoles()->sync($request->visible_to_roles);
        }

        if (!$category->save()) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to update category.',
            ], 500);
        }

        $category->load('visibleToRoles');

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $category,
            'message' => 'Category updated successfully.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_DELETE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to delete company categories.',
            ], 403);
        }

        $request->validate([
            'company_id' => ['required', 'integer'],
            'company_category_id' => ['required', 'integer'],
        ]);

        $companyCategory = CompanyCategory::where('id', $request->company_category_id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$companyCategory) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Company category not found.',
            ], 404);
        }

        if (!$this->isStaff() && !$companyCategory->isVisibleToRole(Auth::user()->role_id)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized to delete this category.',
            ], 403);
        }

        $files = CompanyFile::where('company_category_id', $request->company_category_id)
            ->where('company_id', $request->company_id)
            ->get();

        DB::transaction(function () use ($companyCategory, $files) {
            foreach ($files as $file) {
                $filePath = storage_path('app/public/' . $file->filePath);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $file->delete();
            }
            $companyCategory->delete();
        });

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Company category deleted successfully.',
        ]);
    }
}
