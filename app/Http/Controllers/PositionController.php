<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $allPositions = Position::all();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $allPositions,
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
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $jobPosition = new Position();

            $jobPosition->NKDP = $request->NKDP;
            $jobPosition->jobPosition = $request->jobPosition;


            if ($jobPosition->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $jobPosition,
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
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function show(Position $position)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function edit(Position $position)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $jobPosition = Position::where('id', '=', $id)->first();

            $jobPosition->NKDP = $request->NKDP;
            $jobPosition->jobPosition = $request->jobPosition;


            if ($jobPosition->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $jobPosition,
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
     * @param  \App\Models\Position  $position
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $position = Position::where('id', '=', $id)->first();

            if ($position->delete()) {
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
