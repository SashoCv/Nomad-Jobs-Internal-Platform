<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LoginController extends Controller
{

    public function user(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $user = User::with('role')->where('id', '=', $user_id)->first();

            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
    }


    public function index()
    {
        $users = User::with(['company', 'role'])->get();

        if (Auth::user()->role_id == 1) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $users,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => [],
            ]);
        }
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
            $expires_at =  Carbon::now()->addHours(7);
            $expires_at = date($expires_at);

            if ($user->role->roleName == 'admin' || $user->role->roleName == 'nomadOffice') {
                $companies = Company::all();
            } else {
                $companies = Company::where('id', '=', $user->company_id)->get();
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $companies,
                'role' => $user->role,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'token' => $token,
                'expires_at' => $expires_at
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 403,
                'data' => []
            ]);
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();


        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => []
        ]);
    }


    public function store(Request $request)
    {

        if (Auth::user()->role_id == 1) {
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
            $user->company_id = $request->company_id;

            if ($request->hasFile('userPicture')) {
                Storage::disk('public')->put('userImages', $request->file('userPicture'));
                $name = Storage::disk('public')->put('userImages', $request->file('userPicture'));
                $user->userPicturePath = $name;
                $user->userPictureName = $request->file('userPicture')->getClientOriginalName();
            }

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
        $user = User::where('id', '=', $id)->first();

        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role_id = $request->role_id;
        $user->company_id = $request->company_id;

        if ($request->hasFile('userPicture')) {
            Storage::disk('public')->put('userImages', $request->file('userPicture'));
            $name = Storage::disk('public')->put('userImages', $request->file('userPicture'));
            $user->userPicturePath = $name;
            $user->userPictureName = $request->file('userPicture')->getClientOriginalName();
        }

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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $userDelete = User::findOrFail($id);

        if ($userDelete->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your User has been deleted!',
            ]);
        }
    }
}
