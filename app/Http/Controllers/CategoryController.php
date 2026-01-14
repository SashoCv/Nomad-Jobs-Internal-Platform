<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\HasRolePermissions;
use App\Models\Permission;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if ($this->isStaff()) {

            $categories = Category::all();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        } else {
            // Check for strict role_id match OR if user's role is in the allowed_roles array
            $userRoleId = Auth::user()->role_id;
            
            $categories = Category::where(function ($query) use ($userRoleId) {
                $query->where('role_id', '=', $userRoleId)
                      ->orWhereJsonContains('allowed_roles', (string)$userRoleId)
                      ->orWhereJsonContains('allowed_roles', $userRoleId); // Handle both string/int
            })->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_CREATE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to create categories.',
            ], 403);
        }

        $request->validate([
            'nameOfCategory' => 'required|string|max:255',
            'candidate_id' => 'required|integer',
            'role_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'allowed_roles' => 'nullable|array',
        ]);

        $category = new Category();

        // Determine if user can manage visibility/roles
        $canManageVisibility = $this->isStaff() || $this->checkPermission(Permission::DOCUMENTS_CATEGORIES_MANAGE_VISIBILITY);

        if ($canManageVisibility) {
            // Staff/Managers can set role_id and allowed_roles
            $category->role_id = $request->role_id ?? Auth::user()->role_id;
            
            if ($request->has('allowed_roles')) {
                $category->allowed_roles = $request->allowed_roles;
            }
        } else {
            // Regular users can ONLY create categories for themselves
            $category->role_id = Auth::user()->role_id;
        }

        $category->nameOfCategory = $request->nameOfCategory;
        $category->description = $request->description;
        $category->candidate_id = $request->candidate_id;
        $category->isGenerated = 0;

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
            'message' => 'Failed to create category.',
        ], 500);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        if ($this->checkPermission(Permission::DOCUMENTS_CATEGORIES_UPDATE) || $this->isStaff()) {
            
            // Validate ownership/access
            // If strictly personal category (role_id matches user), or if staff (can edit all)
            // Or if user is in allowed_roles? Usually editing is restricted to creator or admin.
            // For now, assume if they can see it and have EDIT permission, they might be able to edit.
            // But let's stick to standard checks: Owner or Admin.
            
            if (!$this->isStaff() && $category->role_id !== Auth::user()->role_id) {
                 return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'Unauthorized to edit this category.',
                ]);
            }

            $category->nameOfCategory = $request->nameOfCategory;
            $category->description = $request->description;

            // Update roles if staff (or maybe allow owner to expand sharing?)
            if ($this->isStaff()) {
                 if ($request->has('role_id')) {
                     $category->role_id = $request->role_id;
                 }
                 if ($request->has('allowed_roles')) {
                     $category->allowed_roles = $request->allowed_roles;
                 }
            }

            if ($category->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Category updated successfully.',
                    'data' => $category
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'status' => 403,
            'message' => 'Insufficient permissions.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_CATEGORIES_DELETE)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Insufficient permissions to delete categories.',
            ], 403);
        }

        $candidate_id = $request->candidate_id;
        $category_id = $request->category_id;

        $category = Category::where('id', '=', $category_id)->where('candidate_id', '=', $candidate_id)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Category not found.',
            ], 404);
        }

        $files = File::where('category_id', '=', $category_id)->where('candidate_id', '=', $candidate_id)->get();

        foreach ($files as $file) {
            $filePath = storage_path() . '/app/public/' . $file->filePath;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $file->delete();
        }

        if ($category->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Category deleted successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 500,
            'message' => 'Failed to delete category.',
        ], 500);
    }
}
