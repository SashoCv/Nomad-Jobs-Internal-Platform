<?php

namespace App\Http\Controllers;

use App\Models\AssignedJob;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyAdress;
use App\Models\CompanyEmail;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOwner;
use App\Services\CompanyService;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Class CompanyController
 *
 * Handles CRUD operations for companies with role-based access control
 */
class CompanyController extends Controller
{
    use HasRolePermissions;

    const ROLE_COMPANY_USER = 3;
    const ROLE_OWNER = 5;

    public function __construct(
        private CompanyService $companyService
    ) {}

    /**
     * Display a listing of companies based on user role.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $companies = match(true) {
            $this->isStaff() => $this->getCompaniesForAdmin(),
            $user->role_id === self::ROLE_COMPANY_USER => $this->getCompaniesForCompanyUser($user),
            $user->role_id === self::ROLE_OWNER => $this->getCompaniesForOwner($user),
            $user->role_id === Role::AGENT => $this->getCompaniesForAgent($user),
            default => collect([])
        };

        return response()->json([
            'status' => 200,
            'data' => $companies
        ]);
    }

    /**
     * Get companies for admin users
     */
    private function getCompaniesForAdmin()
    {
        return Company::with(['company_addresses','company_addresses.city', 'companyEmails'])->get();
    }

    /**
     * Get companies for company users
     */
    private function getCompaniesForCompanyUser($user)
    {
        return Company::where('id', $user->company_id)->get(['id', 'nameOfCompany']);
    }

    /**
     * Get companies for owner users
     */
    private function getCompaniesForOwner($user)
    {
        $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
        return Company::with(['company_addresses','company_addresses.city', 'companyEmails'])->whereIn('id', $companyIds)->get();
    }

    /**
     * Get companies for agent users (only companies from their assigned job postings)
     */
    private function getCompaniesForAgent($user)
    {
        $companyIds = AssignedJob::where('assigned_jobs.user_id', $user->id)
            ->join('company_jobs', 'assigned_jobs.company_job_id', '=', 'company_jobs.id')
            ->whereNull('company_jobs.deleted_at')
            ->pluck('company_jobs.company_id')
            ->unique();

        return Company::whereIn('id', $companyIds)->get(['id', 'nameOfCompany']);
    }

    /**
     * Validate if company exists by EIK
     *
     * @param string $eik
     * @return bool
     */
    private function validateCompanyByEik(string $eik): bool
    {
        return Company::byEik($eik)->exists();
    }

    /**
     * Store a newly created company in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'data' => []
            ]);
        }

        try {
            if ($this->validateCompanyByEik($request->EIK)) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Company with this EIK already exists!'
                ]);
            }

            $companyData = $request->only([
                'nameOfCompany', 'nameOfCompanyLatin', 'address', 'website',
                'phoneNumber', 'EIK', 'contactPerson', 'EGN', 'dateBornDirector',
                'companyCity', 'industry_id', 'foreignersLC12', 'description',
                'nameOfContactPerson', 'phoneOfContactPerson', 'director_idCard',
                'director_date_of_issue_idCard', 'companyPhone'
            ]);

            $companyData['commissionRate'] = $request->commissionRate === 'null' ? null : $request->commissionRate;

            if ($request->employedByMonths && $request->employedByMonths !== 'null') {
                $companyData['employedByMonths'] = json_decode($request->employedByMonths, true);
            } else {
                $companyData['employedByMonths'] = null;
            }

            $company = $this->companyService->createCompany($companyData, $request);

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $company->load(['company_addresses', 'companyEmails'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating company: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error creating company'
            ]);
        }
    }

    /**
     * Display the specified company.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();

        $company = match(true) {
            $this->isStaff() || $this->checkPermission(Permission::AGENT_COMPANIES_READ) =>
                Company::with(['industry', 'company_addresses.city', 'companyEmails'])->where('id', $id)->first(),
            $user->role_id === self::ROLE_COMPANY_USER =>
                Company::with(['industry', 'company_addresses.city', 'companyEmails'])
                    ->where('id', $user->company_id)
                    ->first(),
            $user->role_id === self::ROLE_OWNER =>
                Company::with(['industry', 'company_addresses.city', 'companyEmails'])
                    ->whereIn('id', UserOwner::where('user_id', $user->id)->pluck('company_id'))
                    ->where('id', $id)
                    ->first(),
            default => null
        };

        if (!$company) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $company
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified company in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized'
            ]);
        }

        try {
            $company = Company::findOrFail($id);

            $updateData = $request->only([
                'nameOfCompany', 'nameOfCompanyLatin', 'address', 'website',
                'phoneNumber', 'EIK', 'contactPerson', 'EGN', 'dateBornDirector',
                'companyCity', 'industry_id', 'foreignersLC12', 'description',
                'nameOfContactPerson', 'phoneOfContactPerson', 'director_idCard',
                'director_date_of_issue_idCard', 'companyPhone'
            ]);

            $updateData['commissionRate'] = ($request->commissionRate === 'null' || $request->commissionRate === '') ? null : $request->commissionRate;

            if ($request->employedByMonths && $request->employedByMonths !== 'null') {
                $updateData['employedByMonths'] = json_decode($request->employedByMonths, true);
            } else {
                $updateData['employedByMonths'] = null;
            }

            $company = $this->companyService->updateCompany($company, $updateData, $request);

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $company->load(['company_addresses', 'companyEmails'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating company: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error updating company'
            ]);
        }
    }

    /**
     * Remove the specified company from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->isStaff()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized'
            ]);
        }

        try {
            $company = Company::findOrFail($id);
            $this->companyService->deleteCompany($company);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Company deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting company: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error deleting company'
            ]);
        }
    }
}
