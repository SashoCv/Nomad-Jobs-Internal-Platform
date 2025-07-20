<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Traits\HasRolePermissions;
use App\Models\CompanyCategory;
use App\Models\CompanyFile;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\Role;
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
            if($this->isStaff()) {

            $companyServiceContracts = CompanyServiceContract::with(['company','contractPricing','contractPricing.status','company.companyFiles'])->get();

            $transformer = new TransformCompanyServiceContract();
            $transformedData = $transformer->transform($companyServiceContracts);

            return response()->json($transformedData);
            } else if (Auth::user()->role_id ==3) {
                $companyId = Auth::user()->company_id;
                $companyServiceContracts = CompanyServiceContract::with(['company','contractPricing','contractPricing.status','company.companyFiles'])
                    ->where('company_id', $companyId)
                    ->get();

                $transformer = new TransformCompanyServiceContract();
                $transformedData = $transformer->transform($companyServiceContracts);

                return response()->json($transformedData);
            } else if( Auth::user()->role_id == 5) {
                $companyIds = UserOwner::where('user_id', Auth::id())
                    ->pluck('company_id');
                $companyServiceContracts = CompanyServiceContract::with(['company','contractPricing','contractPricing.status','company.companyFiles'])
                    ->whereIn('company_id', $companyIds)
                    ->get();

                $transformer = new TransformCompanyServiceContract();

                $transformedData = $transformer->transform($companyServiceContracts);

                return response()->json($transformedData);
            }
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
            if(Auth::user()->role_id == 1) {
                $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'contractNumber' => 'required|string|max:255',
                    'agreement_type' => 'required|in:' . implode(',', [
                            CompanyServiceContract::AGREEMENT_TYPE_STANDARD,
                            // Add more agreement types here if needed
                        ]),
                    'status' => 'required|in:' . implode(',', [
                            CompanyServiceContract::STATUS_PENDING,
                            CompanyServiceContract::STATUS_ACTIVE,
                            CompanyServiceContract::STATUS_EXPIRED,
                            CompanyServiceContract::STATUS_TERMINATED
                        ]),
                    'contractDate' => 'required|date',
                ]);


                $companyServiceContract = new CompanyServiceContract($request->all());
                $companyServiceContract->save();

                return response()->json($companyServiceContract, 201);
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create contract: ' . $e->getMessage()], 400);
        }
    }


    public function storeContractFileForCompany(Request $request)
    {
        try {
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
                $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'contractNumber' => 'required|string|max:255',
                    'agreement_type' => 'required|in:' . implode(',', [
                            CompanyServiceContract::AGREEMENT_TYPE_STANDARD,
                            // Add more agreement types here if needed
                        ]),
                    'status' => 'required|in:' . implode(',', [
                            CompanyServiceContract::STATUS_PENDING,
                            CompanyServiceContract::STATUS_ACTIVE,
                            CompanyServiceContract::STATUS_EXPIRED,
                            CompanyServiceContract::STATUS_TERMINATED
                        ]),
                    'contractDate' => 'required|date',
                ]);

                $companyServiceContract = CompanyServiceContract::findOrFail($id);
                $companyServiceContract->fill($request->all());
                $companyServiceContract->save();

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
