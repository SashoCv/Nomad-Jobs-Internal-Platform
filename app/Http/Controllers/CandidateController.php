<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Education;
use App\Models\Experience;
use App\Models\File;
use App\Models\MedicalInsurance;
use App\Models\Position;
use App\Models\User;
use App\Models\UserOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Support\Facades\Log;
use PDF;
use PhpOffice\PhpWord\Writer\PDF\DomPDF;
use Svg\Tag\Rect;

class CandidateController extends Controller
{

    public function getCandidatesWhoseContractsAreExpiring()
    {
        $currentDate = date('Y-m-d');
        $fourMonthsBefore = date('Y-m-d', strtotime($currentDate . ' + 4 months'));
        $candidates = Candidate::select('id', 'fullName','date','contractPeriodDate', 'company_id', 'status_id', 'position_id')
        ->with([
            'company' => function ($query) {
                $query->select('id', 'nameOfCompany', 'EIK');
            },
            'status' => function ($query) {
                $query->select('id', 'nameOfStatus');
            },
            'position' => function ($query) {
                $query->select('id', 'jobPosition');
            }
        ])
            ->where('contractPeriodDate', '<=', $fourMonthsBefore)
            ->orderBy('contractPeriodDate', 'desc')
            ->paginate();

       return response()->json([
           'success' => true,
           'status' => 200,
           'data' => $candidates,
       ]);
    }
    public function scriptForSeasonal()
    {
        $candidates = Candidate::where('contractType','=','90days')->get();


        foreach ($candidates as $candidate) {
            $year = date('Y', strtotime($candidate->date));
            $month = date('m', strtotime($candidate->date));

            if ($month >= 5 && $month <= 9) {
                $candidate->seasonal = 'summer' . '/' . $year;
            } else if ($month >= 11 || $month <= 2) {
                $candidate->seasonal = 'winter' . '/' . ($month >= 11 ? $year : $year - 1);
            }
            else if ($month >= 2 && $month <= 5) {
                $candidate->seasonal = 'spring' . '/' . $year;
            }
            else if ($month >= 8 && $month <= 11) {
                $candidate->seasonal = 'autumn' . '/' . $year;
            }
            $candidate->save();
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Seasonal added to all candidates',
            'data' => $candidates ?? []
        ]);
    }


    public function scriptForAddedBy()
    {
        $candidates = Candidate::all();

        foreach ($candidates as $candidate) {
            if ($candidate->user_id == null) {
                $candidate->addedBy = 11;
            } else {
                $candidate->addedBy = $candidate->user_id;
            }
            $candidate->save();
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Added by added to all candidates',
        ]);
    }

    public function getFirstQuartal()
    {
        $candidates = Candidate::all();
        $currentYear = date('Y');

        $firstQuartal = "1" . "/" . $currentYear;

        foreach ($candidates as $candidate) {
            // Extract quartal and year from the candidate's quartal
            $candidateParts = explode('/', $candidate->quartal);
            $candidateQuartal = intval($candidateParts[0]); // Extract quartal
            $candidateYear = intval($candidateParts[1]); // Extract year

            // Check if candidate's year is earlier or if it's the same year but with a smaller quartal
            if ($candidateYear < $currentYear || ($candidateYear == $currentYear && $candidateQuartal < 1)) {
                $firstQuartal = $candidate->quartal;
                $currentYear = $candidateYear; // Update current year for future comparisons
            }
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'date' => $firstQuartal,
        ]);
    }
    public function addQuartalToAllCandidates()
    {
        $candidates = Candidate::all();

        foreach ($candidates as $candidate) {
            $candidateDate = $candidate->date;
            $candidateYear = date('Y', strtotime($candidateDate));
            $candidateMonth = date('m', strtotime($candidateDate));

            if ($candidateMonth >= 1 && $candidateMonth <= 3) {
                $quartal = '1' . "/" . $candidateYear;
            } else if ($candidateMonth >= 4 && $candidateMonth <= 6) {
                $quartal = '2' . "/" . $candidateYear;
            } else if ($candidateMonth >= 7 && $candidateMonth <= 9) {
                $quartal = '3' . "/" . $candidateYear;
            } else if ($candidateMonth >= 10 && $candidateMonth <= 12) {
                $quartal = '4' . "/" . $candidateYear;
            }

            $candidate->quartal = $quartal;
            $candidate->save();
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Quartal added to all candidates',
        ]);
    }

    public function generateCandidatePdf(Request $request)
    {
        if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2){
            $candidateId = $request->candidateId;
            $candidate = Candidate::where('id', '=', $candidateId)->first();


            return PDF::loadView('cvTemplate', compact('candidate'))->download('candidate.pdf');
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'You are not authorized to generate pdf',
            ]);
        }

    }

    public function getCandidatesForCompany($id)
    {

        try {
            if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $candidates = Candidate::where('company_id', '=', $id)->select('id', 'fullName')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $candidates,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'You are not authorized to perform this action',
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to get candidates',
            ]);
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $query = Candidate::with(['company', 'status', 'position'])->orderBy('id', 'desc');
        } else if (Auth::user()->role_id == 3) {
            $query = Candidate::where('company_id', '=', Auth::user()->company_id)
                ->where('type_id', '=', 1)->orderBy('id', 'desc');
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => []
            ]);
        }
        $candidates = $query->paginate(25);

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $candidates,
        ]);
    }


    public function employees()
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $query = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 2)->orderBy('id', 'desc');
        } else if (Auth::user()->role_id == 3) {
            $query = Candidate::where('company_id', '=', Auth::user()->company_id)
                ->where('type_id', '=', 2);
        } else {
            return response()->json([
                'success' => false,
                'status' => 401,
                'data' => []
            ]);
        }
        $employees = $query->paginate(25);

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $employees,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
            $person->contractPeriod = $request->contractPeriod;
            $person->contractType = $request->contractType;
            $person->position_id = $request->position_id;
            $person->dossierNumber = $request->dossierNumber;
            $person->notes = $request->notes;
            $person->user_id = $request->user_id;
            $person->addedBy = Auth::user()->id;
            $educations = $request->education ?? [];
            $experiences = $request->experience ?? [];
            $person->agent_id = $request->agent_id ?? null;
            $person->startContractDate = $request->startContractDate ?? null;
            $person->endContractDate = $request->endContractDate ?? null;


            preg_match('/\d+/', $request->contractPeriod, $matches);
            $contractPeriod = isset($matches[0]) ? (int) $matches[0] : null;

            if($contractPeriod === null){
                $contractPeriodDate = null;
            } else {
                $date = Carbon::parse($request->date);
                $contractPeriodDate = $date->addYears($contractPeriod);
            }

            $person->contractPeriodDate = $contractPeriodDate;

            if ($request->case_id === 'null') {
                $case_id = Null;
            } else {
                $case_id = $request->case_id;
            }
            $person->case_id = $case_id;

            $quartalyYear = date('Y', strtotime($request->date));
            $quartalyMonth = date('m', strtotime($request->date));

            if ($quartalyMonth >= 1 && $quartalyMonth <= 3) {
                $quartal = '1' . "/" . $quartalyYear;
            } else if ($quartalyMonth >= 4 && $quartalyMonth <= 6) {
                $quartal = '2' . "/" . $quartalyYear;
            } else if ($quartalyMonth >= 7 && $quartalyMonth <= 9) {
                $quartal = '3' . "/" . $quartalyYear;
            } else if ($quartalyMonth >= 10 && $quartalyMonth <= 12) {
                $quartal = '4' . "/" . $quartalyYear;
            }

            $person->quartal = $quartal;

            if($request->contractType == '90days'){
                if ($quartalyMonth > 5 && $quartalyMonth < 9) {
                    $person->seasonal = 'summer' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 11 || $quartalyMonth <= 2) {
                    $person->seasonal = 'winter' . '/' . ($quartalyMonth > 11 ? $quartalyYear : $quartalyYear - 1);
                } else if ($quartalyMonth > 2 && $quartalyMonth <= 5) {
                    $person->seasonal = 'spring' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 8 && $quartalyMonth <= 11) {
                    $person->seasonal = 'autumn' . '/' . $quartalyYear;
                }
            } else {
                $person->seasonal = Null;
            }




            if ($request->hasFile('personPassport')) {
                Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $name = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $person->passportPath = $name;
                $person->passportName = $request->file('personPassport')->getClientOriginalName();
            }


            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

            if ($person->save()) {

                $jobPositionDocument = Position::where('id', $request->position_id)->first();

                if ($jobPositionDocument->positionPath != Null) {
                    $file = new File();

                    $file->candidate_id = $person->id;
                    $file->category_id = 8;
                    $file->fileName = $jobPositionDocument->positionName;
                    $file->filePath = $jobPositionDocument->positionPath;
                    $file->autoGenerated = 1;
                    $file->deleteFile = 2;

                    $file->save();
                }

                $storeCategory = new Category();
                $storeCategory->candidate_id = $person->id;
                $storeCategory->nameOfCategory = "Documents For Arrival Candidates";
                $storeCategory->role_id = 2;
                $storeCategory->isGenerated = 0;

                $storeCategory->save();

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        $query = Candidate::with(['categories', 'company', 'position'])->where('id', $id);

        if ($roleId == 1 || $roleId == 2) {
            $person = $query->first();

            $agent = AgentCandidate::where('candidate_id', $id)->first();
            $person->agentFullName = $agent ? User::find($agent->user_id)->firstName . ' ' . User::find($agent->user_id)->lastName : null;
        } elseif ($roleId == 3) {
            $person = $query->where('company_id', $user->company_id)->first();
        } elseif ($roleId == 5) {
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('company_id', $companyIds)->first();
        } elseif ($roleId == 4) {
            $candidateIds = AgentCandidate::where('user_id', $user->id)->pluck('candidate_id');
            $person = $query->whereIn('id', $candidateIds)->first();
        } else {
            $person = null;
        }

        if ($person) {
            $person->arrival = Arrival::where('candidate_id', $id)->exists();
            $person->medicalInsurance = MedicalInsurance::where('candidate_id', $id)->get() ?? [];

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $person,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'status' => 404,
            'data' => [],
        ], 404);
    }
    public function showPerson($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $person = Candidate::where('id', '=', $id)->first();
        } else if (Auth::user()->role_id == 3) {
            $person = Candidate::where('id', '=', $id)->where('company_id', Auth::user()->company_id)->first();
        } else if (Auth::user()->role_id == 5) {
            $userOwners = UserOwner::where('user_id', '=', Auth::user()->id)->get();
            $userOwnersArray = [];
            foreach ($userOwners as $userOwner) {
                array_push($userOwnersArray, $userOwner->company_id);
            }
            $person = Candidate::where('id', '=', $id)->whereIn('company_id', $userOwnersArray)->first();
        }

        $education = Education::where('candidate_id', '=', $id)->get();
        if(isset($education)){
            $person->education = $education;
        } else {
            $person->education = [];
        }

        $workExperience = Experience::where('candidate_id', '=', $id)->get();
        if(isset($workExperience)){
            $person->workExperience = $workExperience;
        } else {
            $person->workExperience = [];
        }

        if (isset($person)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $person,
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'status' => 500,
                'data' => [],
            ], 500);
        }
    }



    public function showPersonNew($id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $person = DB::table('candidates')
                ->join('companies', 'companies.id', '=', 'candidates.company_id')
                ->where('candidates.id', $id)
                ->select('candidates.*', 'companies.nameOfCompany')->first();
        } else if (Auth::user()->role_id == 3) {
            $person = DB::table('candidates')
                ->join('companies', 'companies.id', '=', 'candidates.company_id')
                ->where('candidates.id', $id)
                ->where('candidates.company_id', Auth::user()->company_id)
                ->select('candidates.*', 'companies.nameOfCompany')->first();
        } else if (Auth::user()->role_id == 5) {
            $userOwners = UserOwner::where('user_id', '=', Auth::user()->id)->get();
            $userOwnersArray = [];
            foreach ($userOwners as $userOwner) {
                array_push($userOwnersArray, $userOwner->company_id);
            }
            $person = DB::table('candidates')
                ->join('companies', 'companies.id', '=', 'candidates.company_id')
                ->where('candidates.id', $id)
                ->whereIn('candidates.company_id', $userOwnersArray)
                ->select('candidates.*', 'companies.nameOfCompany')->first();
        }
        if (isset($person)) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $person,
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'status' => 500,
                'data' => [],
            ], 500);
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

        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            $files = File::where('candidate_id', '=', $id)->where('autoGenerated', '=', 1)->where('deleteFile', '0')->get();


            foreach ($files as $file) {
                unlink(storage_path() . '/app/public/' . $file->filePath);
                $file->delete();
            }


            $person = Candidate::where('id', '=', $id)->first();

            $person->status_id = $request->status_id;
            $person->type_id = 1;
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
            $person->contractPeriod = $request->contractPeriod;
            $person->contractType = $request->contractType;
            $person->position_id = $request->position_id;
            $person->dossierNumber = $request->dossierNumber;
            $person->notes = $request->notes;
            $person->user_id = $request->user_id;
            $person->case_id = $request->case_id;
            $person->agent_id = $request->agent_id ?? null;
            $person->startContractDate = $request->startContractDate;
            $person->endContractDate = $request->endContractDate;


            $quartalyYear = date('Y', strtotime($request->date));
            $quartalyMonth = date('m', strtotime($request->date));
            $person->quartal = $quartalyMonth . "/" . $quartalyYear;

            preg_match('/\d+/', $request->contractPeriod, $matches);
            $contractPeriod = isset($matches[0]) ? (int) $matches[0] : null;

            if($contractPeriod === null){
                $contractPeriodDate = null;
            } else {
                $date = Carbon::parse($request->date);
                $contractPeriodDate = $date->addYears($contractPeriod);
            }

            $person->contractPeriodDate = $contractPeriodDate;

            if($request->contractType == '90days'){
                if ($quartalyMonth > 5 && $quartalyMonth < 9) {
                    $person->seasonal = 'summer' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 11 || $quartalyMonth <= 2) {
                    $person->seasonal = 'winter' . '/' . ($quartalyMonth > 11 ? $quartalyYear : $quartalyYear - 1);
                } else if ($quartalyMonth > 2 && $quartalyMonth <= 5) {
                    $person->seasonal = 'spring' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 8 && $quartalyMonth <= 11) {
                    $person->seasonal = 'autumn' . '/' . $quartalyYear;
                }
            } else {
                $person->seasonal = Null;
            }

            if ($request->hasFile('personPassport')) {
                Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $name = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $person->passportPath = $name;
                $person->passportName = $request->file('personPassport')->getClientOriginalName();
            }


            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }


            if ($person->save()) {
                $newPerson = Candidate::with('position')->where('id', '=', $id)->first();
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


    public function extendContractForCandidate(Request $request, $id)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
            $oldPerson = Candidate::where('id', '=', $id)->first();
            $contractPeriodNumber = $oldPerson->contractPeriodNumber;
            $newContractPeriodNumber = $contractPeriodNumber + 1;

            $person = new Candidate();

            $person->contractPeriodNumber = $newContractPeriodNumber;
            $person->status_id = $request->status_id;
            $person->type_id = 1;
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
            $person->contractPeriod = $request->contractPeriod;
            $person->contractType = $request->contractType;
            $person->position_id = $request->position_id;
            $person->dossierNumber = $request->dossierNumber;
            $person->notes = $request->notes;
            $person->user_id = $request->user_id ?? null;
            $person->case_id = $request->case_id ?? null;
            $person->agent_id = $request->agent_id ?? null;


            $quartalyYear = date('Y', strtotime($request->date));
            $quartalyMonth = date('m', strtotime($request->date));
            $person->quartal = $quartalyMonth . "/" . $quartalyYear;

            preg_match('/\d+/', $request->contractPeriod, $matches);
            $contractPeriod = isset($matches[0]) ? (int) $matches[0] : null;

            if($contractPeriod === null){
                $contractPeriodDate = null;
            } else {
                $date = Carbon::parse($request->date);
                $contractPeriodDate = $date->addYears($contractPeriod);
            }

            $person->contractPeriodDate = $contractPeriodDate;

            if($request->contractType == '90days'){
                if ($quartalyMonth > 5 && $quartalyMonth < 9) {
                    $person->seasonal = 'summer' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 11 || $quartalyMonth <= 2) {
                    $person->seasonal = 'winter' . '/' . ($quartalyMonth > 11 ? $quartalyYear : $quartalyYear - 1);
                } else if ($quartalyMonth > 2 && $quartalyMonth <= 5) {
                    $person->seasonal = 'spring' . '/' . $quartalyYear;
                } else if ($quartalyMonth > 8 && $quartalyMonth <= 11) {
                    $person->seasonal = 'autumn' . '/' . $quartalyYear;
                }
            } else {
                $person->seasonal = Null;
            }

            if ($request->hasFile('personPassport')) {
                Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $name = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $person->passportPath = $name;
                $person->passportName = $request->file('personPassport')->getClientOriginalName();
            }


            if ($request->hasFile('personPicture')) {
                Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $name = Storage::disk('public')->put('companyImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

            Log::info('person', [$person]);
            if ($person->save()) {
                $newPerson = Candidate::with('position')->where('id', '=', $person->id)->first();
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
                'message' => 'You are not authorized to perform this action',
            ]);
        }
    }


    public function worker($id)
    {
        $worker = Candidate::where('id', '=', $id)->first();

        $worker->type_id = 2;
        $worker->status_id = 10;

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
        try {
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $personDelete = Candidate::findOrFail($id);

                $files = File::where('candidate_id', '=', $id)->get();

                if ($files->count() > 0) {
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
                }

                if ($personDelete->delete()) {
                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'message' => 'Proof! Your employ has been deleted!',
                    ]);
                }
            } else if (Auth::user()->role_id == 4) {
                $personDelete = Candidate::findOrFail($id);

                if ($personDelete->update_at != null) {
                    return response()->json([
                        'success' => false,
                        'status' => 500,
                        'message' => 'You can not delete this candidate!',
                    ]);
                }

                $agentCandidate = AgentCandidate::where('candidate_id', '=', $id)->where('user_id', '=', Auth::user()->id)->first();
                $agentCandidate->delete();

                if ($personDelete->delete()) {
                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'message' => 'Proof! Your employ has been deleted!',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::info("message", $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Something went wrong!',
            ]);
        }
    }
}
