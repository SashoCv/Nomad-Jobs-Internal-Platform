<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->role_id == 1) {
            $candidates = Candidate::where('type_id', '=', 1)->get();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $candidates,
            ]);
        } else {
            $candidates = Candidate::where('company_id', '=', Auth::user()->company_id)
                ->where('type_id', '=', 1)
                ->get();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $candidates,
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
        if (Auth::user()->role_id == 1) {

            $person = new Candidate();

            $person->status_id = $request->status_id;
            $person->type_id = $request->type_id;
            $person->company_id = $request->company_id;
            $person->firstName = $request->firstName;
            $person->lastName = $request->lastName;
            $person->gender = $request->gender;
            $person->email = $request->email;
            $person->nationality = $request->nationality;
            $person->date = $request->date;
            $person->phoneNumber = $request->phoneNumber;
            $person->address = $request->address;
            $person->passport = $request->passport;

            if ($person->save()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $person,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => [],
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 501,
                'data' => [],
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $person = File::with(['candidate','category'])->where('candidate_id','=',$id)->get();

        if (isset($person)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $person,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => [],
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function edit(Candidate $candidate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Candidate $candidate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Candidate $candidate)
    {
        //
    }
}
