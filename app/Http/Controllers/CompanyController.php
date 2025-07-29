<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyAdress;
use App\Models\File;
use App\Models\User;
use App\Models\UserOwner;
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
        return Company::with('company_addresses')->get();
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
        return Company::with('company_addresses')->whereIn('id', $companyIds)->get();
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
     * Handle file upload for company
     *
     * @param Request $request
     * @param Company $company
     * @param string $fileField
     * @param string $pathField
     * @param string $nameField
     */
    private function handleFileUpload(Request $request, Company $company, string $fileField, string $pathField, string $nameField): void
    {
        if ($request->hasFile($fileField)) {
            $path = Storage::disk('public')->put('companyImages', $request->file($fileField));
            $company->$pathField = $path;
            $company->$nameField = $request->file($fileField)->getClientOriginalName();
        }
    }

    /**
     * Handle company addresses creation/update
     *
     * @param Company $company
     * @param array $addresses
     * @param bool $isUpdate
     */
    private function handleCompanyAddresses(Company $company, array $addresses, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            CompanyAdress::where('company_id', $company->id)->delete();
        }

        foreach ($addresses as $address) {
            CompanyAdress::create([
                'company_id' => $company->id,
                'address' => $address['address'],
                'city' => $address['city'],
                'state' => $address['state'],
                'zip_code' => $address['zip_code']
            ]);
        }
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

            DB::beginTransaction();

            $companyData = $request->only([
                'nameOfCompany', 'address', 'email', 'companyEmail', 'website',
                'phoneNumber', 'EIK', 'contactPerson', 'EGN', 'dateBornDirector',
                'companyCity', 'industry_id', 'foreignersLC12', 'description',
                'nameOfContactPerson', 'phoneOfContactPerson', 'director_idCard',
                'director_date_of_issue_idCard'
            ]);

            $companyData['commissionRate'] = $request->commissionRate === 'null' ? null : $request->commissionRate;
            
            // Parse employedByMonths JSON string to array so Laravel can cast it properly
            if ($request->employedByMonths && $request->employedByMonths !== 'null') {
                $companyData['employedByMonths'] = json_decode($request->employedByMonths, true);
            } else {
                $companyData['employedByMonths'] = null;
            }

            $company = Company::create($companyData);

            $this->handleFileUpload($request, $company, 'companyLogo', 'logoPath', 'logoName');
            $this->handleFileUpload($request, $company, 'companyStamp', 'stampPath', 'stampName');

            $company->save();

            $companyAddresses = json_decode($request->company_addresses, true);
            if ($companyAddresses) {
                $this->handleCompanyAddresses($company, $companyAddresses);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $company->load('company_addresses')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            $this->isStaff() =>
                Company::with(['industry', 'company_addresses'])->find($id),
            $user->role_id === self::ROLE_COMPANY_USER =>
                Company::with(['industry', 'company_addresses'])
                    ->where('id', $user->company_id)
                    ->first(),
            $user->role_id === self::ROLE_OWNER =>
                Company::with(['industry', 'company_addresses'])
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

            DB::beginTransaction();

            $updateData = $request->only([
                'nameOfCompany', 'address', 'email', 'companyEmail', 'website',
                'phoneNumber', 'EIK', 'contactPerson', 'EGN', 'dateBornDirector',
                'companyCity', 'industry_id', 'foreignersLC12', 'description',
                'nameOfContactPerson', 'phoneOfContactPerson', 'director_idCard',
                'director_date_of_issue_idCard'
            ]);

            $updateData['commissionRate'] = $request->commissionRate === 'null' ? null : $request->commissionRate;
            
            // Parse employedByMonths JSON string to array so Laravel can cast it properly
            if ($request->employedByMonths && $request->employedByMonths !== 'null') {
                $updateData['employedByMonths'] = json_decode($request->employedByMonths, true);
            } else {
                $updateData['employedByMonths'] = null;
            }

            $company->fill($updateData);

            $this->handleFileUpload($request, $company, 'companyLogo', 'logoPath', 'logoName');
            $this->handleFileUpload($request, $company, 'companyStamp', 'stampPath', 'stampName');

            $company->save();

            $companyAddresses = json_decode($request->company_addresses, true);
            if ($companyAddresses) {
                $this->handleCompanyAddresses($company, $companyAddresses, true);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $company->load('company_addresses')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            $company->delete();

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
