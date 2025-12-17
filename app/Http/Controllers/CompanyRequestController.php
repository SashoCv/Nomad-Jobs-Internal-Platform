<?php

namespace App\Http\Controllers;

use App\Models\CompanyRequest;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserOwner;
use App\Traits\HasRolePermissions;
use App\Services\CompanyRequestTransformerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyRequestController extends Controller
{
    use HasRolePermissions;

    public function __construct(
        private CompanyRequestTransformerService $transformerService
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $user = Auth::user();

            $companyRequests = CompanyRequest::with(['companyJob', 'companyJob.company', 'companyJob.user','companyJob.changeLogs.user'])
                ->whereHas('companyJob', function ($query) {
                    $query->whereNotNull('company_id')
                    ->whereHas('company');
                })
                ->get();

            // Filter requests based on user role
            if ($user->hasRole(Role::COMPANY_USER)) {
                $companyRequests = $companyRequests->filter(function ($request) use ($user) {
                    return $request->companyJob && $request->companyJob->company && $request->companyJob->company_id == $user->company_id;
                })->values(); // Reset array keys to ensure JSON array instead of object
            } else if ($user->hasRole(Role::COMPANY_OWNER)) {
                $companyIds = UserOwner::where('user_id', $user->id)->pluck('company_id');
                $companyRequests = $companyRequests->filter(function ($request) use ($companyIds) {
                    return $request->companyJob && $request->companyJob->company && $companyIds->contains($request->companyJob->company_id);
                })->values(); // Reset array keys to ensure JSON array instead of object
            }
            // Staff can see all requests (no filtering needed)

            // Reset array keys after filtering to ensure JSON returns an array, not an object
            $transformedData = $this->transformerService->transformCompanyRequests($companyRequests->values());

            return response()->json([
                "status" => "success",
                "message" => "Company requests retrieved successfully",
                "data" => $transformedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to retrieve company requests",
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveCompanyRequest(Request $request, $companyRequestId)
    {
        try {
            $companyRequest = CompanyRequest::findOrFail($companyRequestId);
            $companyRequest->approved = true;
            $companyRequest->description = $request->input('description', "Approved by " . auth()->user()->firstName . " " . auth()->user()->lastName);

            if($companyRequest->save()){
                $companyJob = $companyRequest->companyJob;
                $companyJob->showJob = true;
                $companyJob->save();
            }

            return response()->json([
                "status" => "success",
                "message" => "Company request approved successfully",
                "data" => $companyRequest
            ], 200);
        } catch (\Exception $e) {
            Log::info("Error approving company request: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Failed to approve company request",
            ], 500);
        }
    }


    public function rejectCompanyRequest($companyRequestId)
    {
        try {
            if (!$this->checkPermission(Permission::COMPANY_JOB_REQUESTS_APPROVE)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            $companyRequest = CompanyRequest::findOrFail($companyRequestId);
            $companyRequest->approved = false;
            $companyRequest->description = "Rejected by " . auth()->user()->firstName . " " . auth()->user()->lastName;

            if($companyRequest->save()){
                $companyJob = $companyRequest->companyJob;
                $companyJob->showJob = false;
                $companyJob->save();
            }

            return response()->json([
                "status" => "success",
                "message" => "Company request rejected successfully",
                "data" => $companyRequest
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to reject company request",
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyRequest  $companyRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPriceBasedOnRequest($companyRequestId)
    {
        try {
            $companyRequest = CompanyRequest::with([
                'companyJob',
                'companyJob.company',
                'companyJob.user',
                'companyJob.company.serviceContracts' => function ($query) {
                    $query->with(['contractPricing' => function ($pricingQuery) {
                        $pricingQuery->with(['contractServiceType', 'status']);
                    }]);
                }
            ])->findOrFail($companyRequestId);

            $serviceContract = $companyRequest->companyJob->company->serviceContracts->first();
            $pricingBaseOnContract = $serviceContract ? $serviceContract->contractPricing : collect();

            $numberOfPositions = $companyRequest->companyJob->number_of_positions;

            $services = $pricingBaseOnContract->map(function ($pricing) use ($numberOfPositions) {
                $servicePrice = (float) $pricing->price;
                $totalForService = $servicePrice * $numberOfPositions;

                return [
                    'serviceType' => $pricing->contractServiceType->name,
                    'price' => $pricing->price,
                    'statusId' => $pricing->status_id,
                    'status' => $pricing->status->nameOfStatus,
                    'totalForService' => number_format($totalForService, 2),
                ];
            })->values();

            $totalAmount = $services->sum(function ($service) {
                return (float) str_replace(',', '', $service['totalForService']);
            });

            $pricingData = [
                'companyId' => $companyRequest->companyJob->company->id,
                'companyName' => $companyRequest->companyJob->company->nameOfCompany,
                'companyEmail' => $companyRequest->companyJob->company->email,
                'companyCity' => $companyRequest->companyJob->company->companyCity,
                'jobTitle' => $companyRequest->companyJob->job_title,
                'salary' => $companyRequest->companyJob->salary,
                'numberOfPositions' => $numberOfPositions,
                'services' => $services,
                'totalServices' => $services->count(),
                'contractType' => $companyRequest->companyJob->contract_type,
                'totalAmount' => number_format($totalAmount, 2),
            ];

            return response()->json([
                "status" => "success",
                "message" => "Pricing data retrieved successfully",
                "data" => $pricingData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to retrieve company request",
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyRequest  $companyRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(CompanyRequest $companyRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyRequest  $companyRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyRequest $companyRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyRequest  $companyRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyRequest $companyRequest)
    {
        //
    }
}
