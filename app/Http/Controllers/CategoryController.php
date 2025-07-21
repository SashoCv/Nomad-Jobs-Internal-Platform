<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\HasRolePermissions;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->role_id == 1) {

            $categories = Category::all();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        } else if (Auth::user()->role_id == 2) {

            $categories = Category::where('role_id', '=', 2)->orWhere('role_id', '=', 3)->get();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        } else {
            $categories = Category::where('role_id', '=', 3)->get();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $categories,
            ]);
        }
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

            $category = new Category();

            $category->role_id = $request->role_id;
            $category->nameOfCategory = $request->nameOfCategory;
            $category->candidate_id = $request->candidate_id;
            $category->isGenerated = 0;

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
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $candidate_id = $request->candidate_id;
        $category_id = $request->category_id;

        $category = Category::where('id', '=', $category_id)->where('candidate_id', '=', $candidate_id)->first();

        $files = File::where('category_id', '=', $category_id)->where('candidate_id', '=', $candidate_id)->get();

        foreach ($files as $file) {
            unlink(storage_path() . '/app/public/' . $file->filePath);
            $file->delete();
        }

        if ($category->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your category has been deleted!',
            ]);
        }
    }
}
