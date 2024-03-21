<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;


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
    
    
    public function admins()
    {
       

            $admins = User::where('role_id', 1)->where('email', '!=', "phoenix.dev.mk@gmail.com")->get();


            return response()->json([
                "status" => 200,
                "data" => $admins
            ]);
  
            return response()->json([
                "status" => 500,
                "message" => "you dont have permission to see admins"
            ]);
        
    }


    public function index()
    {
        $users = User::with(['company', 'role'])->where('id','!=','22')->get();


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

         
            return response()->json([
                'success' => true,
                'status' => 200,
                'role' => $user->role,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'token' => $token,
                'user_id' => Auth()->user()->id,
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
                    'firstName' => 'required',
                    'email' => 'required|email',
                    'password' => 'required',
                ],
                [
                    'firstName' => 'You must to enter a name!',
                    'email' => 'You must to enter a email!',
                    'password' => 'You must to enter a password!',
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

            if ($request->hasFile('signature')) {
                Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                $name = Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                $user->signaturePath = $name;
                $user->signatureName = $request->file('signature')->getClientOriginalName();
            }

            if ($user->save()) {

                $user = User::where('email', $request->email)->first();

                if ($user) {

                    $domain = URL::to('https://nomad-cloud.netlify.app/');
                    $url = $domain;

                    $data['url'] = $url;
                    $data['email'] = $request->email;
                    $data['password'] = $request->password;
                    $data['title'] = 'Login credentials for Nomad Cloud';
                    $data['body'] = "Please click on below link";

                    Mail::send('loginLink', ['data' => $data], function ($message) use ($data) {
                        $message->to($data['email'])->subject($data['title']);
                    });
                }

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
        if (Auth::user()->role_id == 1) {
            $user = User::where('id', '=', $id)->first();

            return response()->json([
                'success' => false,
                'status' => 200,
                'data' => $user
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => ''
            ]);
        }
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

            if($request->company_id === 'null'){
                $company_id = Null;
            } else {
                $company_id = $request->company_id;
            }

            $user->firstName = $request->firstName;
            $user->lastName = $request->lastName;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->role_id = $request->role_id;
            $user->company_id = $company_id;

            if ($request->hasFile('userPicture')) {
                Storage::disk('public')->put('userImages', $request->file('userPicture'));
                $name = Storage::disk('public')->put('userImages', $request->file('userPicture'));
                $user->userPicturePath = $name;
                $user->userPictureName = $request->file('userPicture')->getClientOriginalName();
            }
            
            if ($request->hasFile('signature')) {
                Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                $name = Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                $user->signaturePath = $name;
                $user->signatureName = $request->file('signature')->getClientOriginalName();
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

        if (Auth::user()->role_id == 1) {

            if ($userDelete->delete()) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'message' => 'Proof! Your User has been deleted!',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => ''
            ]);
        }
    }
}
