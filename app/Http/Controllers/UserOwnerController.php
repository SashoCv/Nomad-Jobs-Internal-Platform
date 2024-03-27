<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\UserOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserOwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Models\UserOwner  $userOwner
     * @return \Illuminate\Http\Response
     */
    public function show(UserOwner $userOwner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserOwner  $userOwner
     * @return \Illuminate\Http\Response
     */
    public function edit(UserOwner $userOwner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserOwner  $userOwner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $userOwner = UserOwner::where('company_id', $id)->first();
            $userOwner->user_id = $request->user_id;

            if ($userOwner->save()) {
                return response()->json($userOwner, 200);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'User not found!'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserOwner  $userOwner
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserOwner $userOwner)
    {
        //
    }
}
