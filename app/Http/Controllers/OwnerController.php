<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => 'You dont have permission to add company owner'
                ]);
            }
            $companyOwner = new Owner();
            $companyOwner->name = $request->name;
            $companyOwner->email = $request->email;
            $companyOwner->phone = $request->phone;
            $companyOwner->save();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companyOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => $e
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Owner  $owner
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            if (Auth::user()->role_id === 1 || Auth::user()->role_id === 2) {
                $companyOwner = Owner::with('companies')->where('id', '=', $id)->first();
            } else if (Auth::user()->role_id === 3) {
                $companyOwner = Owner::with('companies')->where('id', '=', $id)->where('company_id', Auth::user()->company_id)->first();
            }
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companyOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => $e
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Owner  $owner
     * @return \Illuminate\Http\Response
     */
    public function edit(Owner $owner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Owner  $owner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => 'You dont have permission to update company owner'
                ]);
            }
            $companyOwner = Owner::where('id', '=', $id)->first();
            $companyOwner->name = $request->name;
            $companyOwner->email = $request->email;
            $companyOwner->phone = $request->phone;
            $companyOwner->save();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companyOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => $e
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Owner  $owner
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => 'You dont have permission to delete company owner'
                ]);
            }
            $companyOwner = Owner::where('id', '=', $id)->first();
            $companyOwner->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companyOwner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => $e
            ]);
        }
    }
}
