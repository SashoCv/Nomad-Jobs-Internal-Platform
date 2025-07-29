<?php

namespace App\Http\Controllers;

use App\Exports\CandidatesExport;
use App\Exports\CandidatesFromStatusHistoriesExport;
use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
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
use App\Models\Status;
use App\Models\Statushistory;
use App\Models\User;
use App\Models\UserOwner;
use App\Models\Role;
use App\Services\CandidateService;
use App\Traits\HasRolePermissions;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\Writer\PDF\DomPDF;
use Svg\Tag\Rect;
use App\Models\Permission;

class CandidateController extends Controller
{
    use HasRolePermissions;
    protected CandidateService $candidateService;

    public function __construct(CandidateService $candidateService)
    {
        $this->candidateService = $candidateService;
    }

    public function getCandidatesWhoseContractsAreExpiring()
    {
        $fourMonthsBefore = Carbon::now()->addMonths(4)->toDateString();

        $candidates = Candidate::select('id', 'fullNameCyrillic as fullName', 'date', 'endContractDate as contractPeriodDate', 'company_id', 'status_id', 'position_id')
            ->with([
                'company:id,nameOfCompany,EIK',
                'latestStatusHistory.status:id,nameOfStatus',
                'position:id,jobPosition'
            ])
            ->whereDate('endContractDate', '<=', $fourMonthsBefore)
            ->orderBy('endContractDate', 'desc')
            ->paginate();

        // Трансформирај ги податоците за да го задржиш истиот формат
        $candidates->getCollection()->transform(function ($candidate) {
            // Зими го статусот од latestStatusHistory наместо директно од candidate
            $latestStatus = $candidate->latestStatusHistory ? $candidate->latestStatusHistory->status : null;

            $candidate->status = $latestStatus;
            unset($candidate->latestStatusHistory); // отстрани го за да не се дуплира

            // Format dates as ISO strings for consistent frontend handling
            $candidate->date = $candidate->date ? Carbon::parse($candidate->date)->toISOString() : null;
            $candidate->contractPeriodDate = $candidate->contractPeriodDate ? Carbon::parse($candidate->contractPeriodDate)->toISOString() : null;

            return $candidate;
        });

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $candidates,
        ]);
    }
    public function scriptForSeasonal(): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->unauthorizedResponse();
        }

        try {
            $updated = $this->candidateService->updateSeasonalForAllCandidates();

            return $this->successResponse(null, "Seasonal updated for {$updated} candidates");
        } catch (\Exception $e) {
            Log::error('Error updating seasonal data: ' . $e->getMessage());
            return $this->errorResponse('Failed to update seasonal data');
        }
    }


    public function scriptForAddedBy(): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->unauthorizedResponse();
        }

        try {
            $updated = $this->candidateService->updateAddedByForAllCandidates();

            return $this->successResponse(null, "Added by updated for {$updated} candidates");
        } catch (\Exception $e) {
            Log::error('Error updating addedBy data: ' . $e->getMessage());
            return $this->errorResponse('Failed to update addedBy data');
        }
    }

    public function getFirstQuartal(): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->unauthorizedResponse();
        }

        try {
            $firstQuartal = $this->candidateService->getFirstQuartal();

            return $this->successResponse(['date' => $firstQuartal]);
        } catch (\Exception $e) {
            Log::error('Error getting first quartal: ' . $e->getMessage());
            return $this->errorResponse('Failed to get first quartal');
        }
    }
    public function addQuartalToAllCandidates(): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->unauthorizedResponse();
        }

        try {
            $updated = $this->candidateService->updateQuartalForAllCandidates();

            return $this->successResponse(null, "Quartal updated for {$updated} candidates");
        } catch (\Exception $e) {
            Log::error('Error updating quartal data: ' . $e->getMessage());
            return $this->errorResponse('Failed to update quartal data');
        }
    }

    public function generateCandidatePdf(Request $request)
    {
        if($this->isStaff()){
            $candidateId = $request->candidateId;
            $candidate = Candidate::where('id', '=', $candidateId)->first();


            return Pdf::loadView('cvTemplate', compact('candidate'))->download('candidate.pdf');
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
            if($this->isStaff()) {
                $candidates = Candidate::where('company_id', '=', $id)->select('id', 'fullNameCyrillic as fullName')->get();

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
     */
    public function index(): JsonResponse
    {
        try {
            $query = $this->buildCandidateQuery();

            if (!$query) {
                return $this->unauthorizedResponse();
            }

            $candidates = $query->candidates()->with(['company', 'status', 'position'])
                ->orderBy('id', 'desc')
                ->paginate(25);

            return $this->successResponse(CandidateResource::collection($candidates)->response()->getData());
        } catch (\Exception $e) {
            Log::error('Error fetching candidates: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch candidates');
        }
    }


    public function employees(): JsonResponse
    {
        try {
            $query = $this->buildCandidateQuery();

            if (!$query) {
                return $this->unauthorizedResponse();
            }

            $employees = $query->employees()->with(['company', 'status', 'position'])
                ->orderBy('id', 'desc')
                ->paginate(25);

            return $this->successResponse(CandidateResource::collection($employees)->response()->getData());
        } catch (\Exception $e) {
            Log::error('Error fetching employees: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch employees');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCandidateRequest $request): JsonResponse
    {
        try {
            $data = $request->all();
            Log::info('Creating candidate with data in STORE', ['data' => $data]);
            $candidate = $this->candidateService->createCandidate($data);

            Log::info('Candidate created successfully', ['candidate_id' => $candidate->id]);
            return $this->successResponse(new CandidateResource($candidate), 'Candidate created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating candidate: ' . $e->getMessage());
            return $this->errorResponse('Failed to create candidate');
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

        if (!$this->checkPermission(Permission::CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = Candidate::with(['categories', 'company', 'position','statusHistories','statusHistories.status'])->where('id', $id);

        if ($this->isStaff()) {
            $person = $query->first();

            $agent = AgentCandidate::where('candidate_id', $id)->first();
            $person->agentFullName = $agent ? User::find($agent->user_id)->firstName . ' ' . User::find($agent->user_id)->lastName : null;
        } elseif ($user->hasRole(Role::COMPANY_USER)) {
            $person = $query->where('company_id', $user->company_id)->first();
            $person->phoneNumber = null;
        } elseif ($user->hasRole(Role::COMPANY_OWNER)) {
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('company_id', $companyIds)->first();
            $person->phoneNumber = null;
        } elseif ($user->hasRole(Role::AGENT)) {
            $candidateIds = AgentCandidate::where('user_id', $user->id)->pluck('candidate_id');
            $person = $query->whereIn('id', $candidateIds)->first();
        } else {
            $person = null;
        }

        if ($person) {

            $education = Education::where('candidate_id', '=', $id)->get();
            if($education){
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
        $user = Auth::user();

        if (!$this->checkPermission(Permission::CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = Candidate::where('id', '=', $id);

        if ($this->isStaff()) {
            // Staff can see any candidate
            $person = $query->first();
        } else if ($user->hasRole(Role::COMPANY_USER)) {
            // Company users can only see candidates from their company
            $person = $query->where('company_id', $user->company_id)->first();
        } else if ($user->hasRole(Role::COMPANY_OWNER)) {
            // Company owners can see candidates from companies they own
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('company_id', $companyIds)->first();
        } else {
            // Default: no access
            $person = null;
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
        $user = Auth::user();

        if (!$this->checkPermission(Permission::CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = DB::table('candidates')
            ->join('companies', 'companies.id', '=', 'candidates.company_id')
            ->where('candidates.id', $id)
            ->select('candidates.*', 'companies.nameOfCompany');

        if ($this->isStaff()) {
            // Staff can see any candidate
            $person = $query->first();
        } else if ($user->hasRole(Role::COMPANY_USER)) {
            // Company users can only see candidates from their company
            $person = $query->where('candidates.company_id', $user->company_id)->first();
        } else if ($user->hasRole(Role::COMPANY_OWNER)) {
            // Company owners can see candidates from companies they own
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('candidates.company_id', $companyIds)->first();
        } else {
            // Default: no access
            $person = null;
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
     */
    public function update(UpdateCandidateRequest $request, $id): JsonResponse
    {
        try {
            $candidate = Candidate::findOrFail($id);
            $data = $request->all();

            $updatedCandidate = $this->candidateService->updateCandidate($candidate, $data);

            return $this->successResponse(new CandidateResource($updatedCandidate), 'Candidate updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating candidate: ' . $e->getMessage());
            return $this->errorResponse('Failed to update candidate');
        }
    }


    public function extendContractForCandidate(Request $request, $id)
    {
        if ($this->isStaff()) {
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


    public function worker($id): JsonResponse
    {
        if (!$this->isStaff()) {
            return $this->unauthorizedResponse();
        }

        try {
            $candidate = Candidate::findOrFail($id);
            $this->candidateService->promoteToEmployee($candidate);

            return $this->successResponse(null, 'Candidate promoted to employee successfully');
        } catch (\Exception $e) {
            Log::error('Error promoting candidate to employee: ' . $e->getMessage());
            return $this->errorResponse('Failed to promote candidate to employee');
        }
    }


    public function destroy($id): JsonResponse
    {
        try {
            $candidate = Candidate::findOrFail($id);

            if ($this->isStaff()) {
                $this->candidateService->deleteCandidate($candidate);
                return $this->successResponse(null, 'Candidate deleted successfully');
            } elseif (Auth::user()->hasRole(Role::AGENT)) {
                return $this->handleAgentDeletion($candidate, $id);
            }

            return $this->unauthorizedResponse();
        } catch (\Exception $e) {
            Log::error('Error deleting candidate: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete candidate');
        }
    }

    protected function handleAgentDeletion(Candidate $candidate, int $id): JsonResponse
    {
        if ($candidate->updated_at !== null) {
            return $this->errorResponse('Cannot delete this candidate', 422);
        }

        $agentCandidate = AgentCandidate::where('candidate_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($agentCandidate) {
            $agentCandidate->delete();
            $candidate->delete();
            return $this->successResponse(null, 'Candidate deleted successfully');
        }

        return $this->errorResponse('Agent candidate relationship not found', 404);
    }


    public function exportCandidates(Request $request)
    {
        try {
            if (!$this->checkPermission(Permission::CANDIDATES_EXPORT)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $filters = [
                'status_id' => $request->status_id ?? null,
                'company_id' => $request->company_id ?? null,
                'searchDate' => $request->searchDate ?? null,
            ];

            $user = Auth::user();

            if ($this->isStaff()) {
                $candidates = Candidate::with(['company', 'latestStatusHistory','latestStatusHistory.status', 'position']);
                if ($filters['status_id']) {
                    $candidates->whereHas('statusHistories', function ($query) use ($filters) {
                        $query->where('status_id', $filters['status_id']);
                    });
                }

                if ($filters['searchDate']){
                    $candidates->whereHas('latestStatusHistory', function ($query) use ($filters) {
                        $query->whereDate('statusDate', $filters['searchDate']);
                });
                }

                if($filters['company_id']) {
                    $candidates->where('company_id', $filters['company_id']);
                }
            } else if ($user->hasRole(Role::COMPANY_USER)) {
                $candidates = Candidate::with(['company', 'statusHistories', 'position'])
                    ->where('company_id', $user->company_id);

                if ($filters['status_id']) {
                    $candidates->whereHas('statusHistories', function ($query) use ($filters) {
                        $query->where('status_id', $filters['status_id']);
                    });
                }
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'data' => []
                ]);
            }

            $candidates = $candidates->get();

            $export = new CandidatesExport($candidates);
            return Excel::download($export, 'candidates.xlsx');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to export candidates',
            ]);
        }
    }

    public function exportCandidatesBasedOnStatus(Request $request)
    {
        try {
            if ($this->isStaff()) {

                $dateFrom = $request->dateFrom ?? null;
                $dateTo = $request->dateTo ?? null;
                $statusId = $request->statusId ?? 1;

                $statusName = Status::where('id', $statusId)->value('nameOfStatus');

                $candidates = Statushistory::with(['candidate', 'candidate.company', 'candidate.position', 'status'])
                    ->where('status_id', $statusId);

                if ($dateFrom && $dateTo) {
                    $candidates->whereBetween('statusDate', [$dateFrom, $dateTo]);
                } elseif ($dateFrom) {
                    $candidates->where('statusDate', '>=', $dateFrom);
                } elseif ($dateTo) {
                    $candidates->where('statusDate', '<=', $dateTo);
                }

                $candidates = $candidates->get();
            } else {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'data' => []
                ]);
            }

            $currentDate = Carbon::now()->format('d-m-Y');
            $export = new CandidatesFromStatusHistoriesExport($candidates);
            $statusName = str_replace('/', '_', $statusName);
            return Excel::download($export, 'candidates_status_' . $statusName . '_' . 'date' . '_' . $currentDate . '.xlsx');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Helper Methods
    protected function isAuthorized(array $allowedRoles): bool
    {
        return in_array(Auth::user()->role_id, $allowedRoles);
    }

    protected function buildCandidateQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();

        if ($this->isStaff()) {
            return Candidate::query();
        } elseif ($user->hasRole(Role::COMPANY_USER)) {
            return Candidate::byCompany($user->company_id);
        } elseif ($user->hasRole(Role::COMPANY_OWNER)) {
            return $this->buildOwnerQuery($user->id);
        } elseif ($user->hasRole(Role::AGENT)) {
            return $this->buildAgentQuery($user->id);
        }

        return null;
    }

    protected function buildOwnerQuery(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        $companyIds = UserOwner::where('user_id', $userId)->pluck('company_id');
        return Candidate::whereIn('company_id', $companyIds);
    }

    protected function buildAgentQuery(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        $candidateIds = AgentCandidate::where('user_id', $userId)->pluck('candidate_id');
        return Candidate::whereIn('id', $candidateIds);
    }

    protected function successResponse($data = null, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function errorResponse(string $message = 'Error', int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => $status,
            'message' => $message,
        ], $status);
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => 401,
            'message' => 'Unauthorized',
        ], 401);
    }

    protected function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => 404,
            'message' => 'Not found',
        ], 404);
    }
}
