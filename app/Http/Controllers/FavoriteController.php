<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $favoriteCandidates = Favorite::where('user_id', '=', $id)->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $favoriteCandidates,
        ]);
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
        $favorite = new Favorite();

        $favorite->user_id = $request->user_id;
        $favorite->candidate_id = $request->candidate_id;
        $favorite->favorite = 1;

        $favoriteCandidateExist = Favorite::where('user_id', '=', $request->user_id)->where('candidate_id', '=', $request->candidate_id)->first();

        if ($favoriteCandidateExist === null) {
            $favorite->save();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $favorite,
            ]);
        } else {
            $favoriteCandidateExist->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => 'You have remove this candidate from your favorite list',
            ]);
        }

        // if ($favorite->save()) {
        //     return response()->json([
        //         'success' => true,
        //         'status' => 200,
        //         'data' => $favorite,
        //     ]);
        // } else {
        //     return response()->json([
        //         'success' => false,
        //         'status' => 500,
        //         'data' => [],
        //     ]);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function show(Favorite $favorite)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function edit(Favorite $favorite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Favorite $favorite)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function destroy(Favorite $favorite)
    {
        //
    }
}
