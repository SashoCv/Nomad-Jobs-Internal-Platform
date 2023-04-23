<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            $person->fullName = $request->fullName;
            $person->fullNameCyrillic = $request->fullNameCyrillic;
            $person->birthday = $request->birthday;
            $person->placeOfBirth = $request->placeOfBirth;
            $person->country = $request->country;
            $person->area = $request->area;
            $person->areaOfResidence = $request->areaOfResidence;
            $person->addressOfResidence = $request->addressOfResidence;
            $person->periodOfResidence = $request->periodOfResidence;
            $person->passportValidUntil = $request->passportValidUntil;
            $person->passportIssuedBy = $request->passportIssuedBy;
            $person->passportIssuedOn = $request->passportIssuedOn;
            $person->addressOfWork = $request->addressOfWork;
            $person->nameOfFacility = $request->nameOfFacility;
            $person->education = $request->education;
            $person->specialty = $request->specialty;
            $person->qualification = $request->qualification;
            $person->contractExtensionPeriod = $request->contractExtensionPeriod;
            $person->salary = $request->salary;
            $person->workingTime = $request->workingTime;
            $person->workingDays = $request->workingDays;
            $person->martialStatus = $request->martialStatus;
            $person->favorite = 0;

            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

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
                'status' => 401,
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
        $files = File::with(['candidate', 'category'])->where('candidate_id', '=', $id)->get();

        if (isset($person)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $files,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => [],
            ]);
        }
    }


    public function favoriteCandidate($id)
    {
        $favoriteCandidate = Candidate::where('id', '=', $id)->first();

        $favoriteCandidate->favorite = 1;

        if ($favoriteCandidate->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $favoriteCandidate,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if (Auth::user()->role_id == 1) {

            $person = Candidate::where('id', '=', $id)->first();

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
            $person->fullName = $request->fullName;
            $person->fullNameCyrillic = $request->fullNameCyrillic;
            $person->birthday = $request->birthday;
            $person->placeOfBirth = $request->placeOfBirth;
            $person->country = $request->country;
            $person->area = $request->area;
            $person->areaOfResidence = $request->areaOfResidence;
            $person->addressOfResidence = $request->addressOfResidence;
            $person->periodOfResidence = $request->periodOfResidence;
            $person->passportValidUntil = $request->passportValidUntil;
            $person->passportIssuedBy = $request->passportIssuedBy;
            $person->passportIssuedOn = $request->passportIssuedOn;
            $person->addressOfWork = $request->addressOfWork;
            $person->nameOfFacility = $request->nameOfFacility;
            $person->education = $request->education;
            $person->specialty = $request->specialty;
            $person->qualification = $request->qualification;
            $person->contractExtensionPeriod = $request->contractExtensionPeriod;
            $person->salary = $request->salary;
            $person->workingTime = $request->workingTime;
            $person->workingDays = $request->workingDays;
            $person->martialStatus = $request->martialStatus;
           

            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

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
                'status' => 401,
                'data' => [],
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $personDelete = Candidate::findOrFail($id);

        if ($personDelete->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your employ has been deleted!',
            ]);
        }
    }
}
