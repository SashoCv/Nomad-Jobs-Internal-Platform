<?php

namespace App\Http\Controllers;

use App\Models\CompanyServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyServiceContractController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $companyServiceContracts = CompanyServiceContract::with(['company','contractPricing'])->get();


        return response()->json($companyServiceContracts);
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
                    'startDate' => 'required|date',
                    'endDate' => 'nullable|date|after_or_equal:startDate',
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyServiceContract $companyServiceContract)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyServiceContract  $companyServiceContract
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyServiceContract $companyServiceContract)
    {
        //
    }
}
