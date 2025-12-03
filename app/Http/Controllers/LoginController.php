<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeSetPasswordEmailJob;
use App\Models\Company;
use App\Models\CompanyServiceContract;
use App\Models\Role;
use App\Traits\HasRolePermissions;
use App\Models\User;
use App\Models\UserOwner;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
        } else if ($role_id == 3 || $role_id == 5){
            $users = [];
        }

        if ($users) {
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
        try {
            $request->validate(
                [
                    'firstName' => 'required',
                    'email' => 'required|email',
                ],
                [
                    'firstName.required' => 'Name is required!',
                    'email.required' => 'Email is required!',
                    'email.email' => 'Please enter a valid email address!',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        }

            // Check if an active (non-deleted) user with this email already exists
            $existingActiveUser = User::where('email', $request->email)->first();
            if ($existingActiveUser) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'A user with this email already exists!',
                ], 422);
            }

            // Check if a soft-deleted user with this email exists - restore and update instead of creating new
            $softDeletedUser = User::withTrashed()->where('email', $request->email)->whereNotNull('deleted_at')->first();
            if ($softDeletedUser) {
                // Restore the soft-deleted user and update their information
                $softDeletedUser->restore();

                $softDeletedUser->firstName = $request->firstName;
                $softDeletedUser->lastName = $request->lastName;
                $softDeletedUser->password = bcrypt(Str::random(32));
                $softDeletedUser->role_id = $request->role_id;
                $softDeletedUser->company_id = $request->company_id;

                if ($request->hasFile('userPicture')) {
                    Storage::disk('public')->put('userImages', $request->file('userPicture'));
                    $name = Storage::disk('public')->put('userImages', $request->file('userPicture'));
                    $softDeletedUser->userPicturePath = $name;
                    $softDeletedUser->userPictureName = $request->file('userPicture')->getClientOriginalName();
                }

                if ($request->hasFile('signature')) {
                    Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                    $name = Storage::disk('public')->put('adminSignatures', $request->file('signature'));
                    $softDeletedUser->signaturePath = $name;
                    $softDeletedUser->signatureName = $request->file('signature')->getClientOriginalName();
                }

                // Validate company user contract requirement
                if ($request->role_id === Role::COMPANY_USER) {
                    $haveAgreement = CompanyServiceContract::where('company_id', $request->company_id)->first();
                    if (!$haveAgreement) {
                        // Re-delete the user since validation failed
                        $softDeletedUser->delete();
                        return response()->json([
                            'success' => false,
                            'status' => 500,
                            'data' => [],
                            'message' => 'This company does not have a service contract. Please contact admin!'
                        ]);
                    }
                }

                if ($softDeletedUser->save()) {
                    // Handle company owner associations
                    if ($softDeletedUser->role_id === Role::COMPANY_OWNER) {
                        // Clear old associations first
                        $oldUserOwners = UserOwner::where('user_id', $softDeletedUser->id)->get();
                        foreach ($oldUserOwners as $oldUserOwner) {
                            $company = Company::find($oldUserOwner->company_id);
                            if ($company) {
                                $company->has_owner = false;
                                $company->save();
                            }
                            $oldUserOwner->delete();
                        }

                        // Create new associations
                        $companiesIds = $request->companies;
                        $companiesArray = array_map('intval', explode(',', $companiesIds));
                        foreach ($companiesArray as $companyId) {
                            $company = Company::find($companyId);
                            if ($company) {
                                $company->has_owner = true;
                                $company->save();

                                $userOwner = new UserOwner();
                                $userOwner->user_id = $softDeletedUser->id;
                                $userOwner->company_id = $companyId;
                                $userOwner->save();
                            }
                        }
                    }

                    // Generate token for password setup
                    $token = Str::random(64);
                    DB::table('password_resets')->where('email', $softDeletedUser->email)->delete();
                    DB::table('password_resets')->insert([
                        'email' => $softDeletedUser->email,
                        'token' => Hash::make($token),
                        'created_at' => Carbon::now(),
                    ]);

                    $setPasswordUrl = config('app.frontend_url', 'https://nomadjobs.cloud') . '/set-password?token=' . $token . '&email=' . urlencode($softDeletedUser->email);

                    SendWelcomeSetPasswordEmailJob::dispatch(
                        $softDeletedUser->email,
                        $setPasswordUrl,
                        $softDeletedUser->firstName
                    );

                    Log::info('Restored soft-deleted user and sent welcome email', [
                        'user_id' => $softDeletedUser->id,
                        'email' => $softDeletedUser->email,
                    ]);

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $softDeletedUser,
                    ]);
                }
            }

            $user = new User();

            $user->firstName = $request->firstName;
            $user->lastName = $request->lastName;
            $user->email = $request->email;
            // Set a temporary random password - user will set their own via email link
            $user->password = bcrypt(Str::random(32));
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

            // Validate to store company user if they dont have a contract
             if($request->role_id === Role::COMPANY_USER){
                $haveAgreement = CompanyServiceContract::where('company_id', $request->company_id)->first();

                if(!$haveAgreement){
                    return response()->json([
                        'success' => false,
                        'status' => 500,
                        'data' => [],
                        'message' => 'This company does not have a service contract. Please contact admin!'
                    ]);
                }
             }

            if ($user->save()) {
                if($user->role_id === Role::COMPANY_OWNER){
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

                // Generate a secure token for the user to set their password
                $token = Str::random(64);

                // Delete any existing tokens for this email
                DB::table('password_resets')->where('email', $user->email)->delete();

                // Store the hashed token
                DB::table('password_resets')->insert([
                    'email' => $user->email,
                    'token' => Hash::make($token),
                    'created_at' => Carbon::now(),
                ]);

                // Build the set password URL
                $setPasswordUrl = config('app.frontend_url', 'https://nomadjobs.cloud') . '/set-password?token=' . $token . '&email=' . urlencode($user->email);

                // Dispatch the welcome email job
                SendWelcomeSetPasswordEmailJob::dispatch(
                    $user->email,
                    $setPasswordUrl,
                    $user->firstName
                );

                Log::info('Welcome set-password email dispatched for new user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

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
            $request->validate([
                'id' => 'required|integer',
                'password' => 'required|string|min:8',
            ]);

            $user = User::where('id', '=', $request->id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'User not found',
                ]);
            }

            $user->password = bcrypt($request->password);

            if ($user->save()) {
                Log::info('Password changed for user by admin', [
                    'user_id' => $user->id,
                    'changed_by' => Auth::id(),
                ]);

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
            Log::error('Error changing password for user', [
                'error' => $e->getMessage(),
            ]);

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
