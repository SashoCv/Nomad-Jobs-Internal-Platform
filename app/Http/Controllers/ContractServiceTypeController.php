<?php

namespace App\Http\Controllers;

use App\Models\ContractServiceType;
use Illuminate\Http\Request;

class ContractServiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $contractServiceTypes = ContractServiceType::all('id', 'name');
            return response()->json($contractServiceTypes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve contract service types: ' . $e->getMessage()], 500);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ContractServiceType  $contractServiceType
     * @return \Illuminate\Http\Response
     */
    public function show(ContractServiceType $contractServiceType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ContractServiceType  $contractServiceType
     * @return \Illuminate\Http\Response
     */
    public function edit(ContractServiceType $contractServiceType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ContractServiceType  $contractServiceType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ContractServiceType $contractServiceType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContractServiceType  $contractServiceType
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContractServiceType $contractServiceType)
    {
        //
    }
}
