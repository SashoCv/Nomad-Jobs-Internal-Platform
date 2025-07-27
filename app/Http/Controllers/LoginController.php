<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Traits\HasRolePermissions;
use App\Models\User;
use App\Models\UserOwner;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;


class LoginController extends Controller
{
    use HasRolePermissions;

    public function user(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $user = User::with(['role', 'role.permissions'])->where('id', $user_id)->first();

            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => $user
            ]);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
    }

    public function roles()
    {
        try {
            $roles = Role::with('permissions')->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $roles
            ]);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
    }

    public function rolesIdAndName()
    {
        try {
            if(Auth::user()->role_id == 1){
                $roles = Role::select('id', 'roleName')->get();
            } else if (Auth::user()->role_id == 2 || Auth::user()->role_id == 8){
                $roles = Role::select('id', 'roleName')->where('id', '=', 3)
                    ->orWhere('id', '=', 5)
                    ->get();
            } else if (Auth::user()->role_id == 9) {
                $roles = Role::select('id', 'roleName')->where('id', '=', 4)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $roles
            ]);
        } catch (Exception $e) {
            Log::info($e->getMessage());
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


    public function index(Request $request)
    {
        $role_id = $request->role_id;

        if($role_id){
            $users = User::with(['company', 'role'])
                ->where('id','!=','22')
                ->where('role_id', $role_id)->get();
        } else if ($this->isStaff()){
            $users = User::with(['company', 'role'])
                ->where('id','!=','22')->get();
        }

        if ($users && $users->count() > 0) {
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
            $expires_at = Carbon::now()->addHours(48)->toDateTimeString();


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
            $user->passwordShow = $request->password;
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
                if($user->role_id === "5"){
                    $companiesIds = $request->companies;
                    $companiesArray = array_map('intval', explode(',', $companiesIds));

                    foreach ($companiesArray as $companyId) {
                        $company = Company::find($companyId);
                        $company->has_owner = true;
                        $company->save();

                        $userOwner = new UserOwner();
                        $userOwner->user_id = $user->id;
                        $userOwner->company_id = $companyId;
                        $userOwner->save();
                    }
                }

                $user = User::where('email', $request->email)->first();
                if ($user) {

                    $domain = URL::to('https://www.nomadjobs.cloud/');
                    $url = $domain;

                    $data['url'] = $url;
                    $data['firstName'] = $request->firstName;
                    $data['lastName'] = $request->lastName;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        if (Auth::user()->role_id == 1) {
            $user = User::where('id', '=', $id)->first();
            $userOwners = UserOwner::where('user_id', $id)->get();
            $companies = [];

            foreach ($userOwners as $userOwner) {
                $company = Company::find($userOwner->company_id);
                if ($company) {
                    $companyData = ['id' => $company->id, 'name' => $company->nameOfCompany];
                    $companies[] = $companyData;
                }
            }
            return response()->json([
                'success' => false,
                'status' => 200,
                'data' => $user,
                'companies' => $companies ?? []
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => ''
            ]);
        }
    }

    public function changePasswordForUser(Request $request)
    {
        try {
            $user = User::where('id', '=', $request->id)->first();
            $user->password = bcrypt($request->password);
            $user->passwordShow = $request->password;

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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'data' => []
            ]);
        }
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
                    $companiesIds = $request->companies;

                    if($companiesIds){
                        $findAllUserOwners = UserOwner::where('user_id', $id)->get();
                            if($findAllUserOwners){
                                foreach($findAllUserOwners as $userOwner){
                                    $company = Company::find($userOwner->company_id);
                                    $company->has_owner = false;
                                    $company->save();
                                    $userOwner->delete();
                                }
                            }

                        $companiesArray = array_map('intval', explode(',', $companiesIds));

                        foreach ($companiesArray as $companyId) {

                            $company = Company::find($companyId);
                            $company->has_owner = true;
                            $company->save();

                            $userOwner = new UserOwner();
                            $userOwner->user_id = $id;
                            $userOwner->company_id = $companyId;
                            $userOwner->save();
                        }
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
                $userOwnerExists = UserOwner::where('user_id', $id)->get();

                if($userOwnerExists){
                    foreach($userOwnerExists as $userOwner){
                        $company = Company::find($userOwner->company_id);
                        $company->has_owner = false;
                        $company->save();
                    }
                }

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
