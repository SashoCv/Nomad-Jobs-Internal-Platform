<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Traits\HasRolePermissions;
use App\Models\CompanyCategory;
use App\Models\CompanyFile;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\Role;
use App\Models\Permission;
use App\Http\Transformers\TransformCompanyServiceContract;
use App\Models\UserOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyServiceContractController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Check if the user is authorized to view the contracts
            $user = Auth::user();

            if (!$this->checkPermission(Permission::COMPANIES_CONTRACTS_READ)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $query = CompanyServiceContract::with(['company','contractPricing','contractPricing.status','company.companyFiles']);

            if ($this->isStaff()) {
                // Staff can see all contracts
                $companyServiceContracts = $query->get();
            } else if ($user->hasRole(Role::COMPANY_USER)) {
                // Company users see only their company's contracts
                $companyServiceContracts = $query->where('company_id', $user->company_id)->get();
            } else if ($user->hasRole(Role::COMPANY_OWNER)) {
                // Company owners see contracts for companies they own
                $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
                $companyServiceContracts = $query->whereIn('company_id', $companyIds)->get();
            } else {
                // Default: no contracts visible
                $companyServiceContracts = collect();
            }

            $transformer = new TransformCompanyServiceContract();
            $transformedData = $transformer->transform($companyServiceContracts);

            return response()->json($transformedData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve contracts: ' . $e->getMessage()], 500);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            if (!$this->checkPermission(Permission::COMPANIES_CONTRACTS_CREATE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $request->validate([
                'company_id' => 'required|exists:companies,id',
                'contractNumber' => 'required|string|max:255',
                'agreement_type' => 'required|in:' . implode(',', [
                        CompanyServiceContract::AGREEMENT_TYPE_ERPR,
                        CompanyServiceContract::AGREEMENT_TYPE_90DAYS,
                    ]),
                'status' => 'required|in:' . implode(',', [
                        CompanyServiceContract::STATUS_PENDING,
                        CompanyServiceContract::STATUS_ACTIVE,
                        CompanyServiceContract::STATUS_EXPIRED,
                        CompanyServiceContract::STATUS_TERMINATED
                    ]),
            ]);

            // If creating an active contract, first check if one already exists
            if ($request->status === CompanyServiceContract::STATUS_ACTIVE) {
                $existingActiveContract = CompanyServiceContract::where('company_id', $request->company_id)
                    ->where('agreement_type', $request->agreement_type)
                    ->where('status', CompanyServiceContract::STATUS_ACTIVE)
                    ->first();

                if ($existingActiveContract) {
                    // Deactivate the existing active contract before creating the new one
                    $existingActiveContract->update(['status' => CompanyServiceContract::STATUS_EXPIRED]);
                }
            }

            $companyServiceContract = new CompanyServiceContract($request->all());
            $companyServiceContract->save();

            return response()->json($companyServiceContract, 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations
            if ($e->getCode() === '23000') {
                return response()->json([
                    'error' => 'Тази компания вече има активен договор от този тип. Моля, първо деактивирайте съществуващия договор.'
                ], 422);
            }
            return response()->json(['error' => 'Failed to create contract: ' . $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create contract: ' . $e->getMessage()], 400);
        }
    }


    public function storeContractFileForCompany(Request $request)
    {
        try {
            if (!$this->checkPermission(Permission::DOCUMENTS_CREATE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $request->validate([
                'company_service_contract_id' => 'required|exists:company_service_contracts,id',
                'file' => 'required|file', // Adjust file types and size as needed
            ]);

            $companyId = CompanyServiceContract::where('id', $request->company_service_contract_id)->value('company_id');
            $file = $request->file('file');

            $dataForCompanyCategory = [
                'role_id' => 3,
                'company_id' => $companyId,
                'companyNameCategory' => 'Contract File'
            ];

            $companyCategory = new CompanyCategory();

            $companyCategory->fill($dataForCompanyCategory);
            $companyCategory->save();

            $dataForCompanyFiles = [
                'company_id' => $companyId,
                'fileName' => 'Contract File',
                'filePath' => Storage::disk('public')->put('company_files', $file),
                'company_category_id' => $companyCategory->id
            ];

            $companyFile = new CompanyFile();
            $companyFile->fill($dataForCompanyFiles);
            if($companyFile->save()) {
                $companyServiceContract = CompanyServiceContract::findOrFail($request->company_service_contract_id);
                $companyServiceContract->status = "active";
                $companyServiceContract->save();
            }

            return response()->json(['message' => 'Contract file stored successfully', 'companyFile' => $companyFile], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to store contract file: ' . $e->getMessage()], 500);
        }
    }



    public function downloadContractFile($companyId)
    {
        try {
            $companyFile = CompanyFile::where('company_id', $companyId)
                ->where('fileName', 'Contract File')
                ->firstOrFail();

            $filePath = storage_path('app/public/' . $companyFile->filePath);
            $getExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->download($filePath, 'contract_file.' .
                $getExtension, [
                'Content-Type' => 'application/octet-stream',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download contract
  file: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyServiceContract  $companyServiceContract
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $companyServiceContract = CompanyServiceContract::with(['company', 'contractPricing'])->findOrFail($id);

        return response()->json($companyServiceContract);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyServiceContract  $companyServiceContract
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyServiceContract $companyServiceContract)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyServiceContract  $companyServiceContract
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            if (!$this->checkPermission(Permission::COMPANIES_CONTRACTS_UPDATE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $request->validate([
                'company_id' => 'required|exists:companies,id',
                'contractNumber' => 'required|string|max:255',
                'agreement_type' => 'required|in:' . implode(',', [
                        CompanyServiceContract::AGREEMENT_TYPE_ERPR,
                        CompanyServiceContract::AGREEMENT_TYPE_90DAYS,
                    ]),
                'status' => 'required|in:' . implode(',', [
                        CompanyServiceContract::STATUS_PENDING,
                        CompanyServiceContract::STATUS_ACTIVE,
                        CompanyServiceContract::STATUS_EXPIRED,
                        CompanyServiceContract::STATUS_TERMINATED
                    ]),
            ]);

            $companyServiceContract = CompanyServiceContract::findOrFail($id);
            $previousStatus = $companyServiceContract->status;
            
            $companyServiceContract->fill($request->all());
            $companyServiceContract->save();
            
            // If updating to active status, use the helper method to handle deactivation
            if ($request->status === CompanyServiceContract::STATUS_ACTIVE && 
                $previousStatus !== CompanyServiceContract::STATUS_ACTIVE) {
                $companyServiceContract->setAsActive();
            }

            return response()->json($companyServiceContract, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update contract: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyServiceContract  $companyServiceContract
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            if (!$this->checkPermission(Permission::COMPANIES_CONTRACTS_DELETE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $contractPricing = ContractPricing::where('company_service_contract_id', $id)->get();
            if($contractPricing->isNotEmpty()) {
                foreach ($contractPricing as $pricing) {
                    $pricing->delete();
                }
            }
            $companyId = CompanyServiceContract::where('id', $id)->value('company_id');
            $companyFiles = CompanyFile::where('company_id', $companyId)->where('fileName', 'Contract File')->get();

            if($companyFiles->isNotEmpty()) {
                foreach ($companyFiles as $file) {
                    Storage::disk('public')->delete($file->filePath);
                    $file->delete();
                }
            }

            $companyServiceContract = CompanyServiceContract::findOrFail($id);
            $companyServiceContract->delete();

            return response()->json(['message' => 'Contract deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete contract: ' . $e->getMessage()], 500);
        }
    }

    public function deleteContractFile($id)
    {
        try {
            if (!$this->checkPermission(Permission::DOCUMENTS_DELETE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $companyId = CompanyServiceContract::where('id', $id)->value('company_id');
            $companyFile = CompanyFile::where('company_id', $companyId)
                ->where('fileName', 'Contract File')->get();

            if ($companyFile->isNotEmpty()) {
                foreach ($companyFile as $file) {
                    Storage::disk('public')->delete($file->filePath);
                    $file->delete();
                }
            }

            return response()->json(['message' => 'Contract file deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete contract file: ' . $e->getMessage()], 500);
        }
    }
}
