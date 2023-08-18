<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Category;
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
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $person = new Candidate();

            $person->status_id = $request->status_id;
            $person->type_id = $request->type_id;
            $person->company_id = $request->company_id;
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
            // $person->NKPD = $request->NKPD;
            // $person->jobPosition = $request->jobPosition;
            $person->contractPeriod = $request->contractPeriod;
            $person->contractType = $request->contractType;
            $person->position_id = $request->position_id;




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
        $person = Candidate::with(['categories','position'])->where('id', '=', $id)->first();

        if (isset($person)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $person,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'status' => 500,
                'data' => [],
            ]);
        }
    }

    public function showPerson($id)
    {
        $person = Candidate::where('id', '=', $id)->first();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $person,
        ]);
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

        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $files = File::where('candidate_id', '=', $id)->where('autoGenerated', '=', 1)->get();

            foreach ($files as $file) {
                unlink(storage_path() . '/app/public/' . $file->filePath);
                $file->delete();
            }

            if($request->education === 'null'){
                $education = Null;
            } else {
                $education = $request->education;
            }
           
            if($request->specialty === 'null'){
                $specialty = Null;
            } else {
                $specialty = $request->specialty;
            }

            if($request->qualification === 'null'){
                $qualification = Null;
            } else {
                $qualification = $request->qualification;
            }

            if($request->address === 'null'){
                $address = Null;
            } else {
                $address = $request->address;
            }

            if($request->area === 'null'){
                $area = Null;
            } else {
                $area = $request->area;
            }

            if($request->areaOfResidence === 'null'){
                $areaOfResidence = Null;
            } else {
                $areaOfResidence = $request->areaOfResidence;
            }
       
            $person = Candidate::where('id', '=', $id)->first();

            $person->status_id = $request->status_id;
            $person->type_id = $request->type_id;
            $person->company_id = $request->company_id;
            $person->gender = $request->gender;
            $person->email = $request->email;
            $person->nationality = $request->nationality;
            $person->date = $request->date;
            $person->phoneNumber = $request->phoneNumber;
            $person->address = $address;
            $person->passport = $request->passport;
            $person->fullName = $request->fullName;
            $person->fullNameCyrillic = $request->fullNameCyrillic;
            $person->birthday = $request->birthday;
            $person->placeOfBirth = $request->placeOfBirth;
            $person->country = $request->country;
            $person->area = $area;
            $person->areaOfResidence = $areaOfResidence;
            $person->addressOfResidence = $request->addressOfResidence;
            $person->periodOfResidence = $request->periodOfResidence;
            $person->passportValidUntil = $request->passportValidUntil;
            $person->passportIssuedBy = $request->passportIssuedBy;
            $person->passportIssuedOn = $request->passportIssuedOn;
            $person->addressOfWork = $request->addressOfWork;
            $person->nameOfFacility = $request->nameOfFacility;
            $person->education = $education;
            $person->specialty = $specialty;
            $person->qualification = $qualification;
            $person->contractExtensionPeriod = $request->contractExtensionPeriod;
            $person->salary = $request->salary;
            $person->workingTime = $request->workingTime;
            $person->workingDays = $request->workingDays;
            $person->martialStatus = $request->martialStatus;
            // $person->NKPD = $request->NKPD;
            // $person->jobPosition = $request->jobPosition;
            $person->contractPeriod = $request->contractPeriod;
            $person->contractType = $request->contractType;
            $person->position_id = $request->position_id;



            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }
            // elseif ($request->file('personPicture') === null || $request->file('personPicture') === '') {
            //     $person->personPicturePath = null;
            //     $person->personPictureName = null;
            // }

            if ($person->save()) {
                $newPerson = Candidate::with('position')->where('id','=',$id)->first();
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $newPerson,
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

    public function worker($id)
    {
        $worker = Candidate::where('id', '=', $id)->first();

        $worker->type_id = 2;

        if ($worker->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Your change status from candidate to worker',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong',
            ]);
        }
    }


    public function destroy($id)
    {
        $personDelete = Candidate::findOrFail($id);

        $files = File::where('candidate_id', '=', $id)->get();

        foreach ($files as $file) {
            if (isset($file->filePath)) {
                unlink(storage_path() . '/app/public/' . $file->filePath);
            }

            $file->delete();
        }
        $categoriesForCandidate = Category::where('candidate_id', '=', $id)->get();

        foreach ($categoriesForCandidate as $category) {
            $category->delete();
        }

        if ($personDelete->delete()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Proof! Your employ has been deleted!',
            ]);
        }
    }
}
