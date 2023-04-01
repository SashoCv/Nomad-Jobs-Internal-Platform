<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with(['company', 'role'])->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $users,
        ]);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = [
            'email' => $request['email'],
            'password' => $request['password'],
        ];


        if (Auth::attempt($credentials)) {
            $user = User::with(['role', 'company'])->where('email', '=', $request->email)->first();

            $token = $user->createToken('token')->plainTextToken;
            $user->token = $token;

            if ($user->role->roleName == 'admin' && $user->role->roleName == 'nomadOffice') {
                $companies = Company::all();
            } else {
                $companies = Company::where('id', '=', $user->company_id)->get();
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companies,
                'token' => $token
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [],
        ]);
    }


    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required',
                'email' => 'required|email',
                'password' => 'required',
                'role_id' => 'required|int'
            ],
            [
                'name' => 'You must to enter a name!',
                'email' => 'You must to enter a email!',
                'password' => 'You must to enter a password!',
                'role_id' => 'You have to choose the role of the user!'
            ]
        );

        $user = new User();

        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role_id = $request->role_id;
        $user->company_id = $request->companyId;


        if ($user->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
