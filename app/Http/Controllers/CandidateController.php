<?php

namespace App\Http\Controllers;

use App\Exports\CandidatesExport;
use App\Exports\CandidatesFromStatusHistoriesExport;
use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Resources\ApplicantResource;
use App\Http\Resources\CandidateResource;
use App\Models\AgentCandidate;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\ContractType;
use App\Models\Education;
use App\Models\Experience;
use App\Models\File;
use App\Models\MedicalInsurance;
use App\Models\Position;
use App\Models\Status;
use App\Models\Statushistory;
use App\Models\Type;
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


    public function types()
    {
        try {
            $allTypes = ContractType::all();
            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $allTypes,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to fetch types',
            ]);
        }
    }

    public function getCandidatesWhoseContractsAreExpiring()
    {
        $fourMonthsBefore = Carbon::now()->addMonths(4)->toDateString();

        // Exclude candidates with terminated/refused statuses
        $excludedStatuses = [
            Status::TERMINATED_CONTRACT,
            Status::REFUSED_MIGRATION,
            Status::REFUSED_CANDIDATE,
            Status::REFUSED_EMPLOYER,
            Status::REFUSED_BY_MIGRATION_OFFICE,
        ];

        $candidates = Candidate::select('id', 'fullNameCyrillic as fullName', 'date', 'endContractDate as contractPeriodDate', 'contractType', 'contract_type_id', 'company_id', 'status_id', 'position_id')
            ->with([
                'company:id,nameOfCompany,EIK',
                'latestStatusHistory.status:id,nameOfStatus',
                'position:id,jobPosition',
                'contractType:id,name,slug'
            ])
            ->whereNotIn('status_id', $excludedStatuses)
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

            $candidates = $query->candidates()->with(['company', 'status', 'position', 'passportRecord'])
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

            $employees = $query->employees()->with(['company', 'status', 'position', 'passportRecord'])
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

            // Debug: Log file fields separately
            Log::info('[DEBUG store] personPassport from input: ' . json_encode($request->input('personPassport')));
            Log::info('[DEBUG store] hasFile personPassport: ' . ($request->hasFile('personPassport') ? 'true' : 'false'));
            Log::info('[DEBUG store] personPicture from input: ' . json_encode($request->input('personPicture')));
            Log::info('[DEBUG store] hasFile personPicture: ' . ($request->hasFile('personPicture') ? 'true' : 'false'));

            if ($request->hasFile('personPassport')) {
                Log::info('[DEBUG store] personPassport file name: ' . $request->file('personPassport')->getClientOriginalName());
            }

            Log::info('Creating candidate with data in STORE', ['data' => $data]);
            $result = $this->candidateService->createCandidate($data);

            Log::info('Candidate created successfully', [
                'candidate_id' => $result['candidate']->id,
                'contract_id' => $result['contract']->id,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'id' => $result['candidate']->id,
                    'contract_id' => $result['contract']->id,
                    'contract_period_number' => $result['contract']->contract_period_number,
                    'candidate' => new CandidateResource($result['candidate']),
                ],
                'message' => 'Candidate created successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating candidate: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->except(['personPassport', 'personPicture'])));
            return $this->errorResponse('Failed to create candidate: ' . $e->getMessage());
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

        if (!$this->checkPermission(Permission::CANDIDATES_READ) && !$this->checkPermission(Permission::AGENT_CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = Candidate::with(['categories', 'company', 'position', 'statusHistories', 'statusHistories.status', 'country', 'companyAddress', 'companyAddress.city', 'passportRecord'])->where('id', $id);

        if ($this->isStaff()) {
            $person = $query->first();

            if ($person->agent_id) {
                $agent = User::find($person->agent_id);
                $person->agentFullName = $agent ? $agent->firstName . ' ' . $agent->lastName : null;
            } else {
                $person->agentFullName = null;
            }
            $agentCandidate = AgentCandidate::where('candidate_id', $id)->first();
            $person->company_job_id = $agentCandidate ? $agentCandidate->company_job_id : null;
        } elseif ($user->hasRole(Role::COMPANY_USER)) {
            $person = $query->where('company_id', $user->company_id)->first();
            $person->phoneNumber = null;
            $agent = AgentCandidate::where('candidate_id', $id)->first();
            $person->company_job_id = $agent ? $agent->company_job_id : null;
        } elseif ($user->hasRole(Role::COMPANY_OWNER)) {
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('company_id', $companyIds)->first();
            $person->phoneNumber = null;
            $agent = AgentCandidate::where('candidate_id', $id)->first();
            $person->company_job_id = $agent ? $agent->company_job_id : null;
        } elseif ($user->hasRole(Role::AGENT)) {
            $candidateIds = AgentCandidate::where('user_id', $user->id)->pluck('candidate_id');
            $person = $query->whereIn('id', $candidateIds)->first();
            if ($person->agent_id) {
                $agent = User::find($person->agent_id);
                $person->agentFullName = $agent ? $agent->firstName . ' ' . $agent->lastName : null;
            } else {
                $person->agentFullName = null;
            }
            $agentCandidate = AgentCandidate::where('candidate_id', $id)->first();
            $person->company_job_id = $agentCandidate ? $agentCandidate->company_job_id : null;
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

        if (!$this->checkPermission(Permission::CANDIDATES_READ) && !$this->checkPermission(Permission::AGENT_CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = Candidate::with(['companyAddress', 'companyAddress.city'])->where('id', '=', $id);

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
            $person->date = $person->date ? Carbon::parse($person->date)->format('Y-m-d') : null;

            // Get company_job_id from agent_candidates if exists
            $agentCandidate = AgentCandidate::where('candidate_id', $id)->first();
            $person->company_job_id = $agentCandidate ? $agentCandidate->company_job_id : null;

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

        if (!$this->checkPermission(Permission::CANDIDATES_READ) && !$this->checkPermission(Permission::AGENT_CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $query = Candidate::with(['company', 'companyAddress.city'])->where('id', $id);

        if ($this->isStaff()) {
            $person = $query->first();
        } else if ($user->hasRole(Role::COMPANY_USER)) {
            $person = $query->where('company_id', $user->company_id)->first();
        } else if ($user->hasRole(Role::COMPANY_OWNER)) {
            $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
            $person = $query->whereIn('company_id', $companyIds)->first();
        } else {
            $person = null;
        }

        if ($person) {
            // Ensure nameOfCompany is sent to match previous DB::table response
            $person->nameOfCompany = $person->company->nameOfCompany ?? null;

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

            // Check if document regeneration should be skipped
            // (used when only non-document fields like phone/email are changed)
            $skipDocumentRegeneration = $request->boolean('skip_document_regeneration', false);

            // Get contract_id if provided - used to clean up only files for a specific contract
            $contractId = $request->has('contract_id') ? (int) $request->input('contract_id') : null;

            $updatedCandidate = $this->candidateService->updateCandidate($candidate, $data, $skipDocumentRegeneration, $contractId);

            if($candidate->status_id == NULL){
                $candidate->status_id = 16;
                $candidate->save();

                $statusHistory = new Statushistory();
                $statusHistory->status_id = 16;
                $statusHistory->candidate_id = $id;
                $statusHistory->statusDate = Carbon::now(); 
                $statusHistory->save();

                Log::info('statusHistories', [$statusHistory]);
            }

            return $this->successResponse(new CandidateResource($updatedCandidate), 'Candidate updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating candidate: ' . $e->getMessage());
            return $this->errorResponse('Failed to update candidate');
        }
    }


    /**
     * Extend contract for existing candidate profile
     * NO LONGER creates duplicate candidate record - creates a new contract instead
     * Implements DUAL WRITE: updates both contracts table and legacy columns
     */
    public function extendContractForCandidate(Request $request, $id)
    {
        if (!$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'You are not authorized to perform this action',
            ]);
        }

        try {
            $candidate = Candidate::findOrFail($id);

            // Prepare contract data from request
            $contractData = [
                'company_id' => $request->company_id,
                'position_id' => $request->position_id,
                'status_id' => $request->status_id,
                'type_id' => 1, // Candidate type for new contract
                'contractType' => $request->contractType,
                'contractPeriod' => $request->contractPeriod,
                'contractExtensionPeriod' => $request->contractExtensionPeriod,
                'startContractDate' => $request->startContractDate,
                'endContractDate' => $request->endContractDate,
                'salary' => $request->salary,
                'workingTime' => $request->workingTime,
                'workingDays' => $request->workingDays,
                'addressOfWork' => $request->addressOfWork,
                'nameOfFacility' => $request->nameOfFacility,
                'company_adresses_id' => $request->company_adresses_id,
                'dossierNumber' => $request->dossierNumber,
                'agent_id' => $request->agent_id,
                'user_id' => $request->user_id,
                'case_id' => $request->case_id,
                'notes' => $request->notes,
                'date' => $request->date,
                // Personal fields to update on the profile
                'fullName' => $request->fullName,
                'fullNameCyrillic' => $request->fullNameCyrillic,
                'email' => $request->email,
                'phoneNumber' => $request->phoneNumber,
                'passport' => $request->passport,
                'passportValidUntil' => $request->passportValidUntil,
                'passportIssuedBy' => $request->passportIssuedBy,
                'passportIssuedOn' => $request->passportIssuedOn,
                'birthday' => $request->birthday,
                'placeOfBirth' => $request->placeOfBirth,
                'nationality' => $request->nationality,
                'gender' => $request->gender,
                'address' => $request->address,
                'area' => $request->area,
                'areaOfResidence' => $request->areaOfResidence,
                'addressOfResidence' => $request->addressOfResidence,
                'periodOfResidence' => $request->periodOfResidence,
                'education' => $request->education,
                'specialty' => $request->specialty,
                'qualification' => $request->qualification,
                'martialStatus' => $request->martialStatus,
            ];

            // Handle file uploads (add to contractData for the service)
            if ($request->hasFile('personPicture')) {
                $contractData['personPicture'] = $request->file('personPicture');
            }
            if ($request->hasFile('personPassport')) {
                $contractData['personPassport'] = $request->file('personPassport');
            }

            // Use the service to extend contract (creates contract record + dual write)
            $result = $this->candidateService->extendCandidateContract($candidate, $contractData);

            Log::info('Contract extended successfully', [
                'candidate_id' => $result['candidate']->id,
                'contract_id' => $result['contract']->id,
                'contract_period_number' => $result['contract']->contract_period_number
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'id' => $result['candidate']->id,  // SAME candidate ID (not a new person!)
                    'contract_id' => $result['contract']->id,  // NEW contract ID
                    'contract_period_number' => $result['contract']->contract_period_number,
                    // Include full candidate data for backward compatibility
                    'candidate' => $result['candidate'],
                ],
                'message' => 'Contract extended successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error extending contract: ' . $e->getMessage(), [
                'candidate_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to extend contract: ' . $e->getMessage(),
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
        $userId = Auth::id();

        $agentCandidate = AgentCandidate::where('candidate_id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($agentCandidate) {
            // Set deleted_by before soft delete
            $agentCandidate->deleted_by = $userId;
            $agentCandidate->save();
            $agentCandidate->delete();

            $candidate->deleted_by = $userId;
            $candidate->save();
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
                'status_id' => $request->searchStatus ?? $request->status_id ?? null,
                'company_id' => $request->searchCompany ?? $request->company_id ?? null,
                'searchDate' => $request->searchDate ?? null,
                'searchAgent' => $request->searchAgent ?? null,
                'searchContractType' => $request->searchContractType ?? $request->contractType ?? null,
                'searchQuartal' => $request->searchQuartal ?? null,
                'searchSeasonal' => $request->searchSeasonal ?? null,
                'searchCaseId' => $request->searchCaseId ?? $request->case_id ?? null,
                'searchAddedBy' => $request->searchAddedBy ?? null,
                'nationality' => $request->nationality ?? null,
                'searchCity' => $request->searchCity ?? null,
                'searchName' => $request->searchName ?? null,
                'dossierNumber' => $request->dossierNumber ?? null,
                'user_id' => $request->user_id ?? null,
            ];

            $user = Auth::user();

            if ($this->isStaff()) {
                $candidates = Candidate::with(['company', 'status', 'position', 'passportRecord']);

                if ($filters['status_id']) {
                    $candidates->where('status_id', $filters['status_id']);
                }

                if ($filters['searchDate']){
                    $candidates->whereHas('statusHistories', function ($query) use ($filters) {
                        $query->whereDate('statusDate', $filters['searchDate']);
                });
                }

                if($filters['company_id']) {
                    $candidates->where('company_id', $filters['company_id']);
                }

                if ($filters['searchAgent']) {
                    $candidates->where('addedBy', $filters['searchAgent']);
                }

                if ($filters['searchContractType']) {
                    $contractType = $filters['searchContractType'];
                    $map = [
                        'ЕРПР 1' => 'ЕРПР 1',
                        'ЕРПР 2' => 'ЕРПР 2',
                        'ЕРПР 3' => 'ЕРПР 3',
                        '90 дни' => '90 дни',
                        '9 месеца' => '9 месеца',
                    ];
                    $contractTypeLatin = $map[$contractType] ?? $contractType;
                    $candidates->where('contractType', $contractTypeLatin);
                }

                if ($filters['searchQuartal']) {
                    $candidates->where('quartal', $filters['searchQuartal']);
                }

                if ($filters['searchSeasonal']) {
                    $candidates->where('seasonal', $filters['searchSeasonal']);
                }

                if ($filters['searchCaseId']) {
                    $candidates->where('case_id', $filters['searchCaseId']);
                }

                if ($filters['searchAddedBy']) {
                    if ($filters['searchAddedBy'] === 'notDefined') {
                        $candidates->whereNull('addedBy');
                    } else {
                        $candidates->where('addedBy', $filters['searchAddedBy']);
                    }
                }

                if ($filters['nationality']) {
                    $candidates->where('nationality', 'like', '%' . $filters['nationality'] . '%');
                }

                if ($filters['searchCity']) {
                    $cityId = (int) $filters['searchCity'];
                    $candidates->whereHas('companyAddress', function ($query) use ($cityId) {
                        $query->where('city_id', $cityId);
                    });
                }

                if ($filters['searchName']) {
                    $candidates->where(function ($query) use ($filters) {
                        $query->where('fullName', 'like', '%' . $filters['searchName'] . '%')
                              ->orWhere('fullNameCyrillic', 'like', '%' . $filters['searchName'] . '%');
                    });
                }

                if ($filters['dossierNumber']) {
                    $candidates->where('dossierNumber', $filters['dossierNumber']);
                }

                if ($filters['user_id']) {
                    $candidates->where('user_id', $filters['user_id']);
                }

            } else if ($user->hasRole(Role::COMPANY_USER)) {
                $candidates = Candidate::with(['company', 'status', 'position', 'passportRecord'])
                    ->where('company_id', $user->company_id);

                if ($filters['status_id']) {
                    $candidates->where('status_id', $filters['status_id']);
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

                $candidates = Statushistory::with(['candidate', 'candidate.company', 'candidate.position','candidate.agent', 'status'])
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

    public function getApprovedCandidates(Request $request): JsonResponse
    {
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $user = Auth::user();

        $query = AgentCandidate::with(['candidate', 'candidate.company','candidate.companyAddress', 'companyJob','user', 'statusForCandidateFromAgent','hrPerson', 'hrAssignment.admin'])
            ->where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereHas('candidate'); // Само кандидати кои имаат candidate relation

        // Ако корисникот е HR, покажувај само кандидати доделени нему
        if ($user->hasRole(Role::HR)) {
            $query->whereHas('hrAssignment', function ($q) use ($user) {
                $q->where('nomad_office_id', $user->id);
            });
        }

        // Filter po datum
        if ($dateFrom && $dateTo) {
            $query->whereBetween('agent_candidates.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
        } elseif ($dateFrom) {
            // Samo dateFrom: od toj datum do denes
            $query->where('agent_candidates.created_at', '>=', $dateFrom.' 00:00:00');
        } elseif ($dateTo) {
            // Samo dateTo: do toj datum
            $query->where('agent_candidates.created_at', '<=', $dateTo.' 23:59:59');
        } else {
            // default: tekovnata godina
            $query->whereYear('agent_candidates.created_at', date('Y'));
        }

        // Filter by company name
        if ($request->searchCompany) {
            $query->whereHas('candidate.company', function ($q) use ($request) {
                $q->where('nameOfCompany', 'like', '%' . $request->searchCompany . '%');
            });
        }

        // Filter by HR employee name
        if ($request->searchHREmployee) {
            $query->whereHas('hrPerson', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('firstName', 'like', '%' . $request->searchHREmployee . '%')
                         ->orWhere('lastName', 'like', '%' . $request->searchHREmployee . '%')
                         ->orWhereRaw("CONCAT(firstName, ' ', lastName) like ?", ['%' . $request->searchHREmployee . '%']);
                });
            });
        }

        // Filter by candidates without HR employee
        if ($request->withoutHR == '1') {
            $query->whereDoesntHave('hrAssignment');
        }

        // Filter by agent name
        if ($request->searchAgent) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('firstName', 'like', '%' . $request->searchAgent . '%')
                         ->orWhere('lastName', 'like', '%' . $request->searchAgent . '%')
                         ->orWhereRaw("CONCAT(firstName, ' ', lastName) like ?", ['%' . $request->searchAgent . '%']);
                });
            });
        }

        // Filter by candidate name
        if ($request->searchCandidate) {
            $query->whereHas('candidate', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('fullName', 'like', '%' . $request->searchCandidate . '%')
                         ->orWhere('fullNameCyrillic', 'like', '%' . $request->searchCandidate . '%');
                });
            });
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $query->paginate(20),
        ]);
    }

    public function getHRStatistics(Request $request): JsonResponse
    {
        // Prepare date filters
        $dateFrom = $request->dateFrom
            ? Carbon::parse($request->dateFrom)->startOfDay()
            : Carbon::now()->startOfYear();

        $dateTo = $request->dateTo
            ? Carbon::parse($request->dateTo)->endOfDay()
            : Carbon::now()->endOfYear();

        $user = Auth::user();

        Log::info('DATE FILTERS', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        // Base query (all statistics MUST use the same filters)
        $baseQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate'); // Само кандидати кои имаат валиден candidate

        // Ако корисникот е HR, филтрирај само негови кандидати
        if ($user->hasRole(Role::HR)) {
            $baseQuery->join('asign_candidate_to_nomad_offices', 'agent_candidates.candidate_id', '=', 'asign_candidate_to_nomad_offices.candidate_id')
                ->where('asign_candidate_to_nomad_offices.nomad_office_id', $user->id);
        }

        // 1. Total approved candidates
        $totalApprovedCandidates = (clone $baseQuery)->count();

        // 2. Total companies with candidates
        $totalCompanies = (clone $baseQuery)
            ->join('company_jobs', 'agent_candidates.company_job_id', '=', 'company_jobs.id')
            ->distinct('company_jobs.company_id')
            ->count('company_jobs.company_id');

        // 3. Total HR employees with assigned candidates
        $totalHREmployeesQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate')
            ->join('asign_candidate_to_nomad_offices', 'agent_candidates.candidate_id', '=', 'asign_candidate_to_nomad_offices.candidate_id')
            ->distinct('asign_candidate_to_nomad_offices.nomad_office_id');

        if ($user->hasRole(Role::HR)) {
            $totalHREmployeesQuery->where('asign_candidate_to_nomad_offices.nomad_office_id', $user->id);
        }

        $totalHREmployees = $totalHREmployeesQuery->count('asign_candidate_to_nomad_offices.nomad_office_id');

        // 4. Candidates created this month
        $candidatesThisMonthQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('deleted_at')
            ->whereYear('agent_candidates.created_at', Carbon::now()->year)
            ->whereMonth('agent_candidates.created_at', Carbon::now()->month)
            ->whereHas('candidate');

        // Ако корисникот е HR, филтрирај само негови кандидати
        if ($user->hasRole(Role::HR)) {
            $candidatesThisMonthQuery->join('asign_candidate_to_nomad_offices as acno', 'agent_candidates.candidate_id', '=', 'acno.candidate_id')
                ->where('acno.nomad_office_id', $user->id);
        }

        $candidatesThisMonth = $candidatesThisMonthQuery->count();

        // 5. Candidates with process started (имаат status_id во candidates табела)
        $candidatesWithProcessQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate')
            ->join('candidates', 'agent_candidates.candidate_id', '=', 'candidates.id')
            ->whereNotNull('candidates.status_id');

        if ($user->hasRole(Role::HR)) {
            $candidatesWithProcessQuery->join('asign_candidate_to_nomad_offices as acno2', 'agent_candidates.candidate_id', '=', 'acno2.candidate_id')
                ->where('acno2.nomad_office_id', $user->id);
        }

        $candidatesWithProcess = $candidatesWithProcessQuery->distinct('agent_candidates.id')->count('agent_candidates.id');

        // 6. Candidates by company (avoid duplicates)
        $candidatesByCompanyQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate')
            ->join('company_jobs', 'agent_candidates.company_job_id', '=', 'company_jobs.id')
            ->join('companies', 'company_jobs.company_id', '=', 'companies.id');

        if ($user->hasRole(Role::HR)) {
            $candidatesByCompanyQuery->join('asign_candidate_to_nomad_offices as acno3', 'agent_candidates.candidate_id', '=', 'acno3.candidate_id')
                ->where('acno3.nomad_office_id', $user->id);
        }

        $candidatesByCompany = $candidatesByCompanyQuery
            ->select('companies.nameOfCompany as name', DB::raw('COUNT(DISTINCT agent_candidates.id) as count'))
            ->groupBy('companies.id', 'companies.nameOfCompany')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($totalApprovedCandidates) {
                $item->percentage = $totalApprovedCandidates > 0
                    ? round(($item->count / $totalApprovedCandidates) * 100, 2)
                    : 0;
                return $item;
            });

        // 7. Candidates by HR
        $candidatesByHRQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate')
            ->join('asign_candidate_to_nomad_offices', 'agent_candidates.candidate_id', '=', 'asign_candidate_to_nomad_offices.candidate_id')
            ->join('users', 'asign_candidate_to_nomad_offices.nomad_office_id', '=', 'users.id');

        if ($user->hasRole(Role::HR)) {
            $candidatesByHRQuery->where('asign_candidate_to_nomad_offices.nomad_office_id', $user->id);
        }

        $candidatesByHR = $candidatesByHRQuery
            ->select(
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as name"),
                DB::raw('COUNT(DISTINCT agent_candidates.id) as count')
            )
            ->groupBy('users.id', 'users.firstName', 'users.lastName')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($totalApprovedCandidates) {
                $item->percentage = $totalApprovedCandidates > 0
                    ? round(($item->count / $totalApprovedCandidates) * 100, 2)
                    : 0;
                return $item;
            });

        // 8. Candidates with process by HR Employee
        $candidatesByHRWithProcessQuery = AgentCandidate::where('status_for_candidate_from_agent_id', 3)
            ->whereNull('agent_candidates.deleted_at')
            ->whereBetween('agent_candidates.created_at', [$dateFrom, $dateTo])
            ->whereHas('candidate')
            ->join('asign_candidate_to_nomad_offices', 'agent_candidates.candidate_id', '=', 'asign_candidate_to_nomad_offices.candidate_id')
            ->join('candidates', 'agent_candidates.candidate_id', '=', 'candidates.id')
            ->whereNotNull('candidates.status_id')
            ->join('users', 'asign_candidate_to_nomad_offices.nomad_office_id', '=', 'users.id');

        if ($user->hasRole(Role::HR)) {
            $candidatesByHRWithProcessQuery->where('asign_candidate_to_nomad_offices.nomad_office_id', $user->id);
        }

        $candidatesByHRWithProcess = $candidatesByHRWithProcessQuery
            ->select(
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as name"),
                DB::raw('COUNT(DISTINCT agent_candidates.id) as count')
            )
            ->groupBy('users.id', 'users.firstName', 'users.lastName')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($candidatesWithProcess) {
                $item->percentage = $candidatesWithProcess > 0
                    ? round(($item->count / $candidatesWithProcess) * 100, 2)
                    : 0;
                return $item;
            });

        // 9. Candidates by month
        $candidatesByMonth = (clone $baseQuery)
            ->select(
                DB::raw('MONTH(agent_candidates.created_at) as month_number'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->get()
            ->map(function ($item) {
                $months = [
                    1 => 'Јануари', 2 => 'Февруари', 3 => 'Март', 4 => 'Април',
                    5 => 'Мај', 6 => 'Јуни', 7 => 'Јули', 8 => 'Август',
                    9 => 'Септември', 10 => 'Октомври', 11 => 'Ноември', 12 => 'Декември'
                ];
                return [
                    'month' => $months[$item->month_number] ?? '',
                    'count' => $item->count
                ];
            });

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [
                'kpi' => [
                    'totalApprovedCandidates' => $totalApprovedCandidates,
                    'totalCompanies' => $totalCompanies,
                    'totalHREmployees' => $totalHREmployees,
                    'candidatesThisMonth' => $candidatesThisMonth,
                    'candidatesWithProcess' => $candidatesWithProcess,
                ],
                'candidatesByCompany' => $candidatesByCompany,
                'candidatesByHR' => $candidatesByHR,
                'candidatesByHRWithProcess' => $candidatesByHRWithProcess,
                'candidatesByMonth' => $candidatesByMonth,
            ],
        ]);
    }

    /**
     * Get applicants (candidates without status) for company users only
     */
    public function getApplicants(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyIds = [];

            Log::info('getApplicants - Start', [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'company_id' => $user->company_id ?? null,
            ]);

            // Check if user is Company User
            if ($user->role_id == Role::COMPANY_USER && $user->company_id) {
                $companyIds = [$user->company_id];
            }
            // Check if user is Company Owner
            elseif ($user->role_id == Role::COMPANY_OWNER) {
                $companyOwner = UserOwner::where('user_id', $user->id)->get();
                $companyIds = $companyOwner->pluck('company_id')->toArray();
            }
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'Само фирмени потребители и собственици имат достъп до апликанти',
                ], 403);
            }

            if (empty($companyIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Потребителят не е свързан с никоя компания',
                ], 403);
            }

            // Get applicants (candidates without status) for these companies
            $query = Candidate::whereIn('company_id', $companyIds)
                ->whereHas('agentCandidates') // додадено - проверува дали постои во agent_candidates
                ->with([
                    'company',
                    'position',
                    'categories',
                    'agentCandidates.companyJob',
                    'agentCandidates.statusForCandidateFromAgent',
                    'country'
                ]);

            // Apply filters
            if ($request->searchName) {
                $query->where(function ($q) use ($request) {
                    $q->where('fullName', 'like', '%' . $request->searchName . '%')
                      ->orWhere('fullNameCyrillic', 'like', '%' . $request->searchName . '%');
                });
            }

            if ($request->searchCompany) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('nameOfCompany', 'like', '%' . $request->searchCompany . '%');
                });
            }

            if ($request->searchContractType) {
                $query->where('contractType', $request->searchContractType);
            }

            if ($request->nationality) {
                $query->where('nationality', 'like', '%' . $request->nationality . '%');
            }

            if ($request->searchAddedBy) {
                $query->where('addedBy', $request->searchAddedBy);
            }

            if ($request->searchAgent) {
                $query->where('agent_id', $request->searchAgent);
            }

            if ($request->searchDate) {
                $query->whereDate('created_at', $request->searchDate);
            }

            $perPage = $request->per_page ?? 25;
            $applicants = $query->orderBy('id', 'desc')->paginate($perPage);

            Log::info('getApplicants - Query executed', [
                'total' => $applicants->total(),
                'per_page' => $perPage,
                'current_page' => $applicants->currentPage(),
            ]);

            Log::info('getApplicants - Before resource transformation');

            $result = $this->successResponse(ApplicantResource::collection($applicants)->response()->getData());

            Log::info('getApplicants - Success');

            return $result;
        } catch (\Exception $e) {
            Log::error('Error fetching applicants: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to fetch applicants');
        }
    }


}
