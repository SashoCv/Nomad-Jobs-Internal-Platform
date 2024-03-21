<?php

namespace App\Http\Controllers;

use App\Models\CompanyOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyOwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            if (Auth::user()->role_id === 1 || Auth::user()->role_id === 2) {
                $companyOwners = CompanyOwner::with('company')->get();
            } else if (Auth::user()->role_id === 3) {
                $companyOwners = CompanyOwner::with('company')->where('company_id', Auth::user()->company_id)->get();
            }
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companyOwners
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
            $companyOwner = new CompanyOwner();
            $companyOwner->company_id = $request->company_id;
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
     * @param  \App\Models\CompanyOwner  $companyOwner
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            if (Auth::user()->role_id === 1 || Auth::user()->role_id === 2) {
                $companyOwner = CompanyOwner::with('company')->where('id', '=', $id)->first();
            } else if (Auth::user()->role_id === 3) {
                $companyOwner = CompanyOwner::with('company')->where('id', '=', $id)->where('company_id', Auth::user()->company_id)->first();
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
     * @param  \App\Models\CompanyOwner  $companyOwner
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyOwner $companyOwner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyOwner  $companyOwner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyOwner $companyOwner)
    {
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'data' => 'You dont have permission to update company owner'
                ]);
            }
            $companyOwner = CompanyOwner::where('id', '=', $request->id)->first();
            $companyOwner->company_id = $request->company_id;
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
     * @param  \App\Models\CompanyOwner  $companyOwner
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
            $companyOwner = CompanyOwner::where('id', '=', $id)->first();
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
