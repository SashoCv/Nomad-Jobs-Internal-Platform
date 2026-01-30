<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentCandidateResource;
use App\Models\CompanyJob;
use App\Traits\HasRolePermissions;
use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Education;
use App\Models\Experience;
use App\Models\File;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserOwner;
use App\Models\CandidateCvPhoto;
use App\Models\CandidatePassport;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use App\Services\CvGeneratorService;
use App\Services\CvDocxGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Shared\ZipArchive;

class AgentCandidateController extends Controller
{
    use HasRolePermissions;
    public function __construct(
    ) {
    }

    public function downloadDocumentsForCandidatesFromAgent($candidateId)
    {
        if(!Auth::user()){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$this->checkPermission(Permission::DOCUMENTS_DOWNLOAD)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
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
        if (!$this->checkPermission(Permission::AGENT_CANDIDATES_CREATE)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

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
        $person->country_id = $request->country_id;
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

        // CV fields
        $person->height = $request->height;
        $person->weight = $request->weight;
        $person->chronic_diseases = $request->chronic_diseases;
        $person->country_of_visa_application = $request->country_of_visa_application;
        $person->has_driving_license = filter_var($request->has_driving_license, FILTER_VALIDATE_BOOLEAN);
        $person->driving_license_category = $request->driving_license_category;
        $person->driving_license_expiry = $request->driving_license_expiry;
        $person->driving_license_country = $request->driving_license_country;
        $person->english_level = $request->english_level;
        $person->russian_level = $request->russian_level;
        $person->other_language = $request->other_language;
        $person->other_language_level = $request->other_language_level;
        $person->children_info = $request->children_info;

        $educations = $request->educations ?? [];
        $experiences = $request->experiences ?? [];
        $person->agent_id = Auth::user()->id;

        if ($request->hasFile('personPassport')) {
            $name = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
            $person->passportPath = $name;
            $person->passportName = $request->file('personPassport')->getClientOriginalName();
        }

        if ($request->hasFile('personPicture')) {
            $name = Storage::disk('public')->put('personImages', $request->file('personPicture'));
            $person->personPicturePath = $name;
            $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
        }

        if($person->save()){
            // Sync passport data to candidate_passports table (dual-write)
            if ($person->passportValidUntil || $person->passport || $person->passportPath) {
                CandidatePassport::updateOrCreate(
                    ['candidate_id' => $person->id],
                    [
                        'passport_number' => $person->passport,
                        'issue_date' => $person->passportIssuedOn,
                        'expiry_date' => $person->passportValidUntil,
                        'issued_by' => $person->passportIssuedBy,
                        'file_path' => $person->passportPath,
                        'file_name' => $person->passportName,
                    ]
                );
            }

            if(count($educations) > 0){
                foreach ($educations as $education) {
                    $newEducation = new Education();
                    $newEducation->candidate_id = $person->id;
                    $newEducation->school_name = $education['school_name'] ?? null;
                    $newEducation->degree = $education['degree'] ?? null;
                    $newEducation->field_of_study = $education['field_of_study'] ?? null;
                    $newEducation->start_date = $education['start_date'] ?? null;
                    $newEducation->end_date = $education['end_date'] ?? null;
                    $newEducation->save();
                }
            }


            if(count($experiences) > 0){
                foreach ($experiences as $experience) {
                    $newExperience = new Experience();
                    $newExperience->candidate_id = $person->id;
                    $newExperience->company_name = $experience['company_name'] ?? null;
                    $newExperience->position = $experience['position'] ?? null;
                    $newExperience->responsibilities = $experience['responsibilities'] ?? null;
                    $newExperience->start_date = $experience['start_date'] ?? null;
                    $newExperience->end_date = $experience['end_date'] ?? null;
                    $newExperience->save();
                }
            }

            // Handle CV Photos
            $this->handleCvPhotoUploads($request, $person);

            $message = sprintf(
                'Агент %s добави кандидат за позиция "%s"',
                Auth::user()->firstName,
                $getCompanyJob->job_title
            );
            
            $notificationData = [
                'message' => $message,
                'type' => $message,
            ];

            $notification = NotificationRepository::createNotification($notificationData);
            UsersNotificationRepository::createNotificationForUsers($notification);

            $candidateData = [
                'user_id' => Auth::user()->id,
                'company_job_id' => (int) $request->company_job_id,
                'candidate_id' => $person->id,
                'status_id' => 1,
            ];

            $categoryForFiles = new Category();
            $categoryForFiles->nameOfCategory = 'files from agent';
            $categoryForFiles->candidate_id = $person->id;
            $categoryForFiles->isGenerated = 0;
            $categoryForFiles->save();
            $categoryForFiles->visibleToRoles()->attach(Role::AGENT);

            // Generate CV DOCX automatically and save it in "files from agent" category
            try {
                $cvService = new CvDocxGeneratorService();
                $cvService->generateAndSaveCv($person);
            } catch (\Exception $e) {
                Log::warning('CV generation failed for candidate ' . $person->id . ': ' . $e->getMessage());
                // Don't fail the whole request if CV generation fails
            }

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
            if (!$this->checkPermission(Permission::CANDIDATES_FROM_AGENT_READ)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

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
            $status = $request->status_for_candidate_from_agent_id;
            $companyJobId = $request->company_job_id;
            $agentId = $request->agent_id;
            $dateFrom = $request->date_from; // očekuvame 'Y-m-d'
            $dateTo = $request->date_to;     // očekuvame 'Y-m-d'

            $user = Auth::user();
            $user_id = $user->id;

            $query = AgentCandidate::with([
                'candidate',
                'companyJob',
                'companyJob.company',
                'statusForCandidateFromAgent',
                'user'
            ])
                ->whereNull('agent_candidates.deleted_at')
                ->whereHas('candidate'); // Само кандидати кои имаат candidate relation

            // Get user's company IDs for Company User/Owner
            $userCompanyIds = [];
            if ($user->hasRole(Role::COMPANY_USER) && $user->company_id) {
                $userCompanyIds = [$user->company_id];
            } elseif ($user->hasRole(Role::COMPANY_OWNER)) {
                $companyOwner = UserOwner::where('user_id', $user->id)->get();
                $userCompanyIds = $companyOwner->pluck('company_id')->toArray();
            }

            // Filter po company_job_id i role
            if ($companyJobId != null) {
                if ($this->isStaff()) {
                    // Staff can see all candidates for this job
                    $query->where('company_job_id', $companyJobId);
                } elseif ($user->hasRole(Role::COMPANY_USER) || $user->hasRole(Role::COMPANY_OWNER)) {
                    // Company users can only see candidates for jobs from their companies
                    $query->where('company_job_id', $companyJobId)
                        ->whereHas('companyJob', function ($q) use ($userCompanyIds) {
                            $q->whereIn('company_id', $userCompanyIds);
                        });
                } elseif ($user->hasRole(Role::AGENT)) {
                    $query->where('user_id', $user_id)
                        ->where('company_job_id', $companyJobId);
                }
            } else {
                // No specific job filter
                if ($user->hasRole(Role::AGENT)) {
                    $query->where('user_id', $user_id);
                } elseif ($user->hasRole(Role::COMPANY_USER) || $user->hasRole(Role::COMPANY_OWNER)) {
                    // Filter by user's companies
                    $query->whereHas('companyJob', function ($q) use ($userCompanyIds) {
                        $q->whereIn('company_id', $userCompanyIds);
                    });
                }
            }

            // Filter po company_id preko relacija
            if ($companyId) {
                $query->whereHas('companyJob', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }

            // Filter po ime
            if ($name) {
                $query->whereHas('candidate', function ($subquery) use ($name) {
                    $subquery->where(function ($q) use ($name) {
                        $q->where('fullName', 'LIKE', '%' . $name . '%')
                          ->orWhere('fullNameCyrillic', 'LIKE', '%' . $name . '%');
                    });
                });
            }

            // Filter po status
            if ($status) {
                $query->where('status_for_candidate_from_agent_id', $status);
            }

            // Filter po agent
            if ($agentId) {
                $query->where('user_id', $agentId);
            }

            // Filter po datum (samo ako nema company_job_id)
            if (!$companyJobId) {
                if ($dateFrom && $dateTo) {
                    $query->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
                } elseif ($dateFrom) {
                    // Samo dateFrom: od toj datum do denes
                    $query->where('created_at', '>=', $dateFrom.' 00:00:00');
                } elseif ($dateTo) {
                    // Samo dateTo: do toj datum
                    $query->where('created_at', '<=', $dateTo.' 23:59:59');
                }
            }
            // Note: No default year filter - show all candidates if no date filter is provided

            // Order po id (najnovi prvo)
            $query->orderBy('agent_candidates.id', 'desc');

            $candidates = $query->paginate(40);

            return AgentCandidateResource::collection($candidates);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get candidates'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            if (!$this->checkPermission(Permission::CANDIDATES_FROM_AGENT_DELETE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

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

    /**
     * Update candidate added by agent
     */
    public function updateCandidateAsAgent(Request $request, $id)
    {
        try {
            if (!$this->checkPermission(Permission::AGENT_CANDIDATES_UPDATE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            // Find the candidate
            $person = Candidate::find($id);
            if (!$person) {
                return response()->json(['message' => 'Candidate not found'], 404);
            }

            // Check if the agent is the one who added this candidate
            if ($person->agent_id != Auth::user()->id) {
                return response()->json(['message' => 'You can only update candidates you added'], 403);
            }
            // Update basic fields
            $fieldsToUpdate = [
                'gender', 'email', 'nationality', 'date', 'phoneNumber',
                'address', 'passport', 'fullName', 'fullNameCyrillic',
                'birthday', 'placeOfBirth', 'country_id', 'area', 'areaOfResidence',
                'addressOfResidence', 'periodOfResidence', 'passportValidUntil',
                'passportIssuedBy', 'passportIssuedOn', 'addressOfWork',
                'nameOfFacility', 'education', 'specialty', 'qualification',
                'contractExtensionPeriod', 'salary', 'workingTime', 'workingDays',
                'martialStatus', 'contractPeriod', 'contractType',
                'dossierNumber', 'notes',
                // CV fields
                'height', 'weight', 'chronic_diseases', 'country_of_visa_application',
                'has_driving_license', 'driving_license_category', 'driving_license_expiry',
                'driving_license_country', 'english_level', 'russian_level',
                'other_language', 'other_language_level', 'children_info'
            ];

            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $value = $request->$field;

                    // Handle boolean fields - convert string 'true'/'false' to actual boolean
                    if ($field === 'has_driving_license') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }

                    $person->$field = $value;
                }
            }

            // Handle passport file update
            if ($request->hasFile('personPassport')) {
                // Store old passport name BEFORE updating (fix for orphan file bug)
                $oldPassportName = $person->passportName;

                // Delete old passport file if exists
                if ($person->passportPath) {
                    Storage::disk('public')->delete($person->passportPath);
                }

                $name = Storage::disk('public')->put('personPassports', $request->file('personPassport'));
                $person->passportPath = $name;
                $person->passportName = $request->file('personPassport')->getClientOriginalName();

                // Update file in files table - search by _passport suffix pattern
                if ($oldPassportName) {
                    // Build the _passport filename pattern from the old name
                    // e.g., "image.png" -> "image_passport.png"
                    $pathInfo = pathinfo($oldPassportName);
                    $oldPassportFileNameWithSuffix = $pathInfo['filename'] . '_passport';
                    if (isset($pathInfo['extension'])) {
                        $oldPassportFileNameWithSuffix .= '.' . $pathInfo['extension'];
                    }

                    // Build the new _passport filename
                    $newPathInfo = pathinfo($person->passportName);
                    $newPassportFileNameWithSuffix = $newPathInfo['filename'] . '_passport';
                    if (isset($newPathInfo['extension'])) {
                        $newPassportFileNameWithSuffix .= '.' . $newPathInfo['extension'];
                    }

                    // Search for passport file by filename pattern (any category with _passport suffix)
                    $passportFile = File::where('candidate_id', $id)
                        ->where('fileName', 'like', '%_passport%')
                        ->first();

                    if ($passportFile) {
                        // Delete old file from storage
                        if ($passportFile->filePath) {
                            Storage::disk('public')->delete($passportFile->filePath);
                        }
                        // Update with new file info
                        $passportFile->fileName = $newPassportFileNameWithSuffix;
                        $passportFile->filePath = $person->passportPath;
                        $passportFile->save();
                    }
                }
            }

            // Handle picture file update
            if ($request->hasFile('personPicture')) {
                // Delete old picture file if exists
                if ($person->personPicturePath) {
                    Storage::disk('public')->delete($person->personPicturePath);
                }

                $name = Storage::disk('public')->put('personImages', $request->file('personPicture'));
                $person->personPicturePath = $name;
                $person->personPictureName = $request->file('personPicture')->getClientOriginalName();
            }

            // Save the person
            if ($person->save()) {
                // Sync passport data to candidate_passports table (dual-write)
                if ($person->passportValidUntil || $person->passport || $person->passportPath) {
                    CandidatePassport::updateOrCreate(
                        ['candidate_id' => $person->id],
                        [
                            'passport_number' => $person->passport,
                            'issue_date' => $person->passportIssuedOn,
                            'expiry_date' => $person->passportValidUntil,
                            'issued_by' => $person->passportIssuedBy,
                            'file_path' => $person->passportPath,
                            'file_name' => $person->passportName,
                        ]
                    );
                }

                // Handle educations update
                if ($request->has('educations')) {
                    // Delete existing educations
                    Education::where('candidate_id', $id)->delete();

                    // Add new educations
                    $educations = $request->educations;

                    // If educations is a string, decode it as JSON
                    if (is_string($educations)) {
                        $educations = json_decode($educations, true) ?? [];
                    }

                    // Ensure it's an array
                    if (!is_array($educations)) {
                        $educations = [];
                    }

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

                // Handle experiences update
                if ($request->has('experiences')) {
                    // Delete existing experiences
                    Experience::where('candidate_id', $id)->delete();

                    // Add new experiences
                    $experiences = $request->experiences;

                    // If experiences is a string, decode it as JSON
                    if (is_string($experiences)) {
                        $experiences = json_decode($experiences, true) ?? [];
                    }

                    // Ensure it's an array
                    if (!is_array($experiences)) {
                        $experiences = [];
                    }

                    foreach ($experiences as $experience) {
                        $newExperience = new Experience();
                        $newExperience->candidate_id = $person->id;
                        $newExperience->company_name = $experience['company_name'];
                        $newExperience->position = $experience['position'];
                        $newExperience->responsibilities = $experience['responsibilities'] ?? null;
                        $newExperience->start_date = $experience['start_date'];
                        $newExperience->end_date = $experience['end_date'];
                        $newExperience->save();
                    }
                }


                // Create notification
                $notificationData = [
                    'message' => 'Агент ' . Auth::user()->firstName . ' редактира кандидат ' . $person->fullName,
                    'type' => 'agent_updated_candidate',
                ];

                $notification = NotificationRepository::createNotification($notificationData);
                UsersNotificationRepository::createNotificationForUsers($notification);

                // Handle CV Photos
                $this->handleCvPhotoUploads($request, $person);

                // Regenerate CV with updated data
                try {
                    // Delete old auto-generated CV file
                    $oldCvFile = File::where('candidate_id', $person->id)
                        ->where('fileName', 'like', 'CV_%')
                        ->where('autoGenerated', 1)
                        ->first();

                    if ($oldCvFile) {
                        // Delete physical file
                        if ($oldCvFile->filePath) {
                            Storage::disk('public')->delete($oldCvFile->filePath);
                        }
                        $oldCvFile->delete();
                    }

                    // Generate new CV
                    $cvService = new CvDocxGeneratorService();
                    $cvService->generateAndSaveCv($person);
                } catch (\Exception $e) {
                    Log::warning('CV regeneration failed for candidate ' . $person->id . ': ' . $e->getMessage());
                    // Don't fail the whole request if CV generation fails
                }

                return response()->json([
                    'message' => 'Candidate updated successfully',
                    'candidate' => $person,
                ], 200);
            } else {
                return response()->json(['message' => 'Failed to update candidate'], 500);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to update candidate: ' . $e->getMessage()], 500);
        }
    }

    // Get details for agent candidate
    public function getDetails($agentCandidateId)
    {
        try {
            $agentCandidate = AgentCandidate::findOrFail($agentCandidateId);
            $details = $agentCandidate->details;

            return response()->json([
                'success' => true,
                'data' => $details,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get details: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create or update details for agent candidate
    public function upsertDetails(Request $request, $agentCandidateId)
    {
        try {
            $agentCandidate = AgentCandidate::findOrFail($agentCandidateId);

            $data = $request->validate([
                'powerOfAttorney' => 'nullable|boolean',
                'personnelReferences' => 'nullable|boolean',
                'accommodationAddress' => 'nullable|boolean',
                'notes' => 'nullable|string',
            ]);

            $details = $agentCandidate->details()->updateOrCreate(
                ['agent_candidate_id' => $agentCandidateId],
                $data
            );

            return response()->json([
                'success' => true,
                'data' => $details,
                'message' => 'Details saved successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and download CV for an agent candidate
     */
    public function generateCv($agentCandidateId)
    {
        try {
            // Find agent candidate with all relations
            $agentCandidate = AgentCandidate::with(['candidate'])->findOrFail($agentCandidateId);

            // Generate CV using service
            $cvService = new CvGeneratorService();
            $pdf = $cvService->generateCv($agentCandidateId);

            // Get candidate name for filename
            $candidateName = $agentCandidate->candidate->fullName ??
                           $agentCandidate->candidate->fullNameCyrillic ??
                           'candidate';

            // Clean filename - remove special characters
            $candidateName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $candidateName);
            $filename = 'CV_' . $candidateName . '_' . date('Y-m-d') . '.pdf';

            // Return PDF as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('CV Generation Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate CV',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle CV photo uploads (workplace, diploma, driving license)
     */
    protected function handleCvPhotoUploads(Request $request, Candidate $candidate): void
    {
        $photoTypes = [
            'workplacePhotos' => CandidateCvPhoto::TYPE_WORKPLACE,
            'diplomaPhotos' => CandidateCvPhoto::TYPE_DIPLOMA,
            'drivingLicensePhoto' => CandidateCvPhoto::TYPE_DRIVING_LICENSE,
        ];

        foreach ($photoTypes as $fieldName => $type) {
            if ($request->hasFile($fieldName)) {
                $files = $request->file($fieldName);

                // Ensure it's an array
                if (!is_array($files)) {
                    $files = [$files];
                }

                // For driving license, only keep one photo - delete existing first
                if ($type === CandidateCvPhoto::TYPE_DRIVING_LICENSE) {
                    $existingPhotos = CandidateCvPhoto::where('candidate_id', $candidate->id)
                        ->where('type', $type)
                        ->get();

                    foreach ($existingPhotos as $photo) {
                        if (Storage::disk('public')->exists($photo->file_path)) {
                            Storage::disk('public')->delete($photo->file_path);
                        }
                        $photo->delete();
                    }
                }

                $directory = 'candidate/' . $candidate->id . '/cv-photos/' . $type;
                $sortOrder = CandidateCvPhoto::where('candidate_id', $candidate->id)
                    ->where('type', $type)
                    ->max('sort_order') ?? 0;

                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = \Illuminate\Support\Str::uuid() . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs($directory, $fileName, 'public');

                        CandidateCvPhoto::create([
                            'candidate_id' => $candidate->id,
                            'type' => $type,
                            'file_path' => $filePath,
                            'file_name' => $file->getClientOriginalName(),
                            'sort_order' => ++$sortOrder,
                        ]);

                        // For driving license, only store one
                        if ($type === CandidateCvPhoto::TYPE_DRIVING_LICENSE) {
                            break;
                        }
                    }
                }
            }
        }
    }
}
