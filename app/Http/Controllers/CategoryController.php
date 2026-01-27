<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\File;
use App\Models\Permission;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    use HasRolePermissions;

    public function index(): JsonResponse
    {
        if ($this->isStaff()) {
            $categories = Category::with('visibleToRoles')->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        }

        $userRoleId = Auth::user()->role_id;

        $categories = Category::whereHas(
            'visibleToRoles',
            fn($query) => $query->where('roles.id', $userRoleId)
        )->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_CREATE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to create categories.',
            ], 403);
        }

        $request->validate([
            'nameOfCategory' => ['required', 'string', 'max:255'],
            'candidate_id' => ['required', 'integer'],
            'visible_to_roles' => ['nullable', 'array'],
            'visible_to_roles.*' => ['integer', 'exists:roles,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = new Category();
        $category->nameOfCategory = $request->nameOfCategory;
        $category->description = $request->description;
        $category->candidate_id = $request->candidate_id;
        $category->isGenerated = 0;

        if (!$category->save()) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to create category.',
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

    public function update(Request $request, Category $category): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_UPDATE) && !$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions.',
            ], 403);
        }

        if (!$this->isStaff() && !$category->isVisibleToRole(Auth::user()->role_id)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized to edit this category.',
            ], 403);
        }

        $request->validate([
            'nameOfCategory' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visible_to_roles' => ['nullable', 'array'],
            'visible_to_roles.*' => ['integer', 'exists:roles,id'],
        ]);

        if ($request->has('nameOfCategory')) {
            $category->nameOfCategory = $request->nameOfCategory;
        }

        if ($request->has('description')) {
            $category->description = $request->description;
        }

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
            'message' => 'Category updated successfully.',
            'data' => $category,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_DELETE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to delete categories.',
            ], 403);
        }

        $request->validate([
            'candidate_id' => ['required', 'integer'],
            'category_id' => ['required', 'integer'],
        ]);

        $category = Category::where('id', $request->category_id)
            ->where('candidate_id', $request->candidate_id)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Category not found.',
            ], 404);
        }

        if (!$this->isStaff() && !$category->isVisibleToRole(Auth::user()->role_id)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized to delete this category.',
            ], 403);
        }

        $files = File::where('category_id', $request->category_id)
            ->where('candidate_id', $request->candidate_id)
            ->get();

        DB::transaction(function () use ($category, $files) {
            foreach ($files as $file) {
                $filePath = storage_path('app/public/' . $file->filePath);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $file->delete();
            }
            $category->delete();
        });

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
