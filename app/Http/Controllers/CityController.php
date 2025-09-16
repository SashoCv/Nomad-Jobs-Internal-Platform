<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $cities = City::all(['id', 'name']);
            return response()->json($cities, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve cities'], 500);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:cities,name',
            ]);

            $city = City::create([
                'name' => $request->name,
            ]);

            return response()->json($city, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create city'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $city = City::findOrFail($id)->only(['id', 'name']);
            return response()->json($city, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'City not found'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\Response
     */
    public function edit(City $city)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:cities,name,' . $id,
            ]);

            $city = City::findOrFail($id);
            $city->name = $request->name;
            $city->save();

            return response()->json($city, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update city'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\Response
     */
    public function destroy(City $city)
    {
        //
    }
}
