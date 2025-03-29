<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentCandidateResource;
use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Education;
use App\Models\Experience;
use App\Models\File;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Shared\ZipArchive;

class AgentCandidateController extends Controller
{
    public function __construct(
    ) {
    }

    public function downloadDocumentsForCandidatesFromAgent($candidateId)
    {
        if(!Auth::user()){
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $candidateCategoryId = Category::where('candidate_id', $candidateId)->where('nameOfCategory', 'files from agent')->first()->id;

        if(!$candidateCategoryId){
            return response()->json(['message' => 'Category not found'], 404);
        }
        $files = File::where('candidate_id', $candidateId)->where('category_id', $candidateCategoryId)->get(['fileName', 'filePath']);

        if(!$files){
            return response()->json(['message' => 'Files not found'], 404);
        }
        $candidate = Candidate::find($candidateId);

        $zip = new ZipArchive();
        $zipFileName = $candidate->fullName . '_agent_documents.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                $filePath = public_path('storage/' . $file->filePath);
                if (file_exists($filePath)) {
                    $fileName = $file->fileName;
                    $fileExtension = substr(strrchr($filePath, '.'), 1);
                    $fileName .= '.' . $fileExtension;
                    $zip->addFile($filePath, $fileName);
                }
            }
            $zip->close();

            return response()->download($zipFilePath, $zipFileName);
        } else {
            return response()->json(['message' => 'Failed to create the zip file'], 500);
        }
    }

    public function agentAddCandidateForAssignedJob(Request $request)
    {
        $getCompanyJob = DB::table('company_jobs')->where('id', $request->company_job_id)->first();
        if(!$getCompanyJob){
            return response()->json(['message' => 'Job not found'], 404);
        }
        $companyId = $getCompanyJob->company_id;
        $person = new Candidate();

        $person->status_id = $request->status_id;
        $person->type_id = "3";
        $person->company_id = $companyId;
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
        $educations = $request->educations ?? [];
        $experiences = $request->experiences ?? [];
        $person->agent_id = Auth::user()->id;

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

        if($person->save()){


            if(count($educations) > 0){
                foreach ($educations as $education) {
                    $newEducation = new Education();
                    $newEducation->candidate_id = $person->id;
                    $newEducation->school_name = $education['school_name'];
                    $newEducation->degree = $education['degree'];
                    $newEducation->field_of_study = $education['field_of_study'];
                    $newEducation->start_date = $education['start_date'];
                    $newEducation->end_date = $education['end_date'];
                    $newEducation->save();
                }
            }


            if(count($experiences) > 0){
                foreach ($experiences as $experience) {
                    $newExperience = new Experience();
                    $newExperience->candidate_id = $person->id;
                    $newExperience->company_name = $experience['company_name'];
                    $newExperience->position = $experience['position'];
                    $newExperience->start_date = $experience['start_date'];
                    $newExperience->end_date = $experience['end_date'];
                    $newExperience->save();
                }
            }

            $notificationData = [
                'message' => 'Agent' . ' ' . Auth::user()->name . ' ' .  'added candidate to job',
                'type' => 'Agent add Candidate for Assigned Job',
            ];

            $candidateData = [
                'user_id' => Auth::user()->id,
                'company_job_id' => (int) $request->company_job_id,
                'candidate_id' => $person->id,
                'status_id' => 1,
            ];

            $categoryForFiles = new Category();
            $categoryForFiles->role_id = 4;
            $categoryForFiles->nameOfCategory = 'files from agent';
            $categoryForFiles->candidate_id = $person->id;
            $categoryForFiles->isGenerated = 0;
            $categoryForFiles->save();


            $passportFile = new File();
            $passportFile->fileName = $person->passportName;
            $passportFile->filePath = $person->passportPath;
            $passportFile->category_id = $categoryForFiles->id;
            $passportFile->candidate_id = $person->id;
            $passportFile->autoGenerated = 1;
            $passportFile->company_restriction = 0;

            $passportFile->save();


            $agentCandidate = new AgentCandidate();

            $agentCandidate->user_id = $candidateData['user_id'];
            $agentCandidate->company_job_id = $candidateData['company_job_id'];
            $agentCandidate->candidate_id = $candidateData['candidate_id'];
            $agentCandidate->status_for_candidate_from_agent_id = $candidateData['status_id'];

            $agentCandidate->save();


            $notification = NotificationRepository::createNotification($notificationData);
            UsersNotificationRepository::createNotificationForUsers($notification);

            return response()->json(
                [
                    'message' => 'Candidate added successfully',
                    'agentCandidate' => $agentCandidate,
                ],
                200
            );
        } else {
            return response()->json(['message' => 'Failed to add candidate'], 500);
        }
    }

    /**
     * Here i get all candidates for assigned job
     */
    public function getCandidatesForAssignedJob($id)
    {
        try {
            $query = AgentCandidate::with(['candidate', 'companyJob', 'companyJob.company', 'statusForCandidateFromAgent', 'user'])
                ->join('company_jobs', 'agent_candidates.company_job_id', '=', 'company_jobs.id');

            $candidates = $query->where('company_job_id', $id)->paginate(20);

            return AgentCandidateResource::collection($candidates);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get candidates'], 500);
        }
    }


    public function getAllCandidatesFromAgents(Request $request)
    {
        try {
            $companyId = $request->company_id;
            $name = $request->name;
            $status = $request->status;
            $companyJobId = $request->company_job_id;
            $agentId = $request->agent_id;



            $user_id = Auth::user()->id;
            $query = AgentCandidate::with(['candidate', 'companyJob', 'companyJob.company', 'statusForCandidateFromAgent', 'user'])
                ->join('company_jobs', 'agent_candidates.company_job_id', '=', 'company_jobs.id')
                ->orderBy('company_jobs.company_id', 'desc');

            if ($request->company_job_id != null) {
                if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 3 || Auth::user()->role_id == 5) {
                    $query->where('company_job_id', $request->company_job_id);
                } else if (Auth::user()->role_id == 4) {
                    $query->where('agent_candidates.user_id', $user_id)
                        ->where('company_job_id', $request->company_job_id);
                }
            } else {
                if (Auth::user()->role_id == 1) {
                    $query->where('nomad_office_id', null);
                } else if (Auth::user()->role_id == 2){
                    $query->where('agent_candidates.nomad_office_id', $user_id);
                } else if (Auth::user()->role_id == 4) {
                    $query->where('agent_candidates.user_id', $user_id);
                }
            }

            if($companyId){
                $query->where('company_jobs.company_id', $companyId);
            }

            if($name){
                $query->whereHas('candidate', function ($subquery) use ($name) {
                    $subquery->where('fullName', 'LIKE', '%' . $name . '%')
                        ->orWhere('fullNameCyrillic', 'LIKE', '%' . $name . '%');
                });
            }

            if($status){
                $query->where('status_for_candidate_from_agent_id', $status);
            }

            if($companyJobId){
                $query->where('company_job_id', $companyJobId);
            }

            if($agentId){
                $query->where('agent_candidates.user_id', $agentId);
            }


            $candidates = $query->paginate(20);

            return AgentCandidateResource::collection($candidates);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get candidates'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $agentCandidate = AgentCandidate::where('candidate_id', $id)->first();
            if ($agentCandidate) {
                $agentCandidate->delete();
                return response()->json(['message' => 'Candidate deleted successfully'], 200);
            } else {
                return response()->json(['message' => 'Candidate not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
