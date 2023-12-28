<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

            if ($request->hasFile('positionDocument')) {
                Storage::disk('public')->put('jopPosition', $request->file('positionDocument'));
                $name = Storage::disk('public')->put('jopPosition', $request->file('positionDocument'));
                $jobPosition->positionPath = $name;
                $jobPosition->positionName = $request->file('positionDocument')->getClientOriginalName();
            }

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

            if ($request->hasFile('positionDocument')) {
                Storage::disk('public')->put('jopPosition', $request->file('positionDocument'));
                $name = Storage::disk('public')->put('jopPosition', $request->file('positionDocument'));
                $jobPosition->positionPath = $name;
                $jobPosition->positionName = $request->file('positionDocument')->getClientOriginalName();
            }

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


    public function destroyDocumentForPosition($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $fileDelete = Position::findOrFail($id);

            $fileDelete->positionName = Null;
            $fileDelete->positionPath = Null;

            if ($fileDelete->save()) {
                unlink(storage_path() . '/app/public/' . $fileDelete->positionPath);

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Proof! Your file has been deleted!',
                ]);
            }
        } else {
            return response()->json([
                'success' => true,
                'status' => 401,
                'message' => 'You dont have access',
            ]);
        }
    }

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
