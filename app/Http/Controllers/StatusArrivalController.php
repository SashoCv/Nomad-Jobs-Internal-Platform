<?php

namespace App\Http\Controllers;

use App\Models\StatusArrival;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusArrivalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $statusArrivals = StatusArrival::select('id', 'statusName')->orderBy('order_statuses')->get();
            } else {
                return response()->json([
                    'message' => 'You are not authorized to view this page'
                ]);
            }

            return response()->json([
                'message' => 'Status Arrivals retrieved successfully',
                'statusArrivals' => $statusArrivals
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
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
     * @param  \App\Models\StatusArrival  $statusArrival
     * @return \Illuminate\Http\Response
     */
    public function show(StatusArrival $statusArrival)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StatusArrival  $statusArrival
     * @return \Illuminate\Http\Response
     */
    public function edit(StatusArrival $statusArrival)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StatusArrival  $statusArrival
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StatusArrival $statusArrival)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StatusArrival  $statusArrival
     * @return \Illuminate\Http\Response
     */
    public function destroy(StatusArrival $statusArrival)
    {
        //
    }
}
