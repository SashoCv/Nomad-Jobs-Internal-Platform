<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;
use App\Models\Permission;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Traits\HasRolePermissions;

class CityController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission(Permission::CITIES_READ)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $query = City::with('country');

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->boolean('all')) {
                $cities = $query->get();
            } else {
                $cities = $query->paginate($request->get('per_page', 15));
            }

            return CityResource::collection($cities);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve cities'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCityRequest $request)
    {
        if (!$this->checkPermission(Permission::CITIES_CREATE)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $city = City::create([
                'name' => $request->name,
                'country_id' => $request->country_id,
            ]);

            $city->load('country');
            return response()->json([
                'message' => 'Градът е добавен успешно',
                'data' => new CityResource($city)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create city'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $city = City::with('country')->find($id);

            if (!$city) {
                return response()->json(['error' => 'City not found'], 404);
            }

            return new CityResource($city);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve city'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCityRequest $request, $id)
    {
        if (!$this->checkPermission(Permission::CITIES_UPDATE)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $city = City::find($id);

            if (!$city) {
                return response()->json(['error' => 'City not found'], 404);
            }

            $city->update([
                'name' => $request->name,
                'country_id' => $request->country_id,
            ]);

            $city->load('country');
            return response()->json([
                'message' => 'Градът е актуализиран успешно',
                'data' => new CityResource($city)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update city'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!$this->checkPermission(Permission::CITIES_DELETE)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $city = City::find($id);

            if (!$city) {
                return response()->json(['error' => 'City not found'], 404);
            }

            // Check if city is used in CompanyAdress
            if (\App\Models\CompanyAdress::where('city_id', $id)->exists()) {
                return response()->json([
                    'message' => 'City is currently in use by company addresses and cannot be deleted.',
                    'errors' => ['usage' => ['City is used']]
                ], 422);
            }

            $city->delete();

            return response()->json([
                'message' => 'Градът е изтрит успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete city'], 500);
        }
    }
}
