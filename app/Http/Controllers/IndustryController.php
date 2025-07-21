<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use App\Traits\HasRolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndustryController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ($this->isStaff()) {

            $allIndustries = Industry::all();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $allIndustries,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'status' => 402,
                'data' => [],
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

            $industry = new Industry();

            $industry->nameOfIndustry = $request->nameOfIndustry;


            if ($industry->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $industry,
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
     * @param  \App\Models\Industry  $industry
     * @return \Illuminate\Http\Response
     */
    public function show(Industry $industry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Industry  $industry
     * @return \Illuminate\Http\Response
     */
    public function edit(Industry $industry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Industry  $industry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($this->isStaff()) {

            $industry = Industry::where('id', '=', $id)->first();

            $industry->nameOfIndustry = $request->nameOfIndustry;


            if ($industry->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $industry,
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Industry  $industry
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($this->isStaff()) {

            $industry = Industry::where('id', '=', $id)->first();

            if ($industry->delete()) {
                return response()->json([
                    'success' => true,
                    'status' => 200
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 403
            ]);
        }
    }
}
