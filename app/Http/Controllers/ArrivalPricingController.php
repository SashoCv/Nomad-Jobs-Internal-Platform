<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalPricing;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ArrivalPricingController extends Controller
{

    public function storeInvoiceForArrivalCandidate(Request $request)
    {
        try {
            $data = $request->validate([
                'candidateId' => 'required|exists:candidates,id',
                'status_id' => 'required|exists:statuses,id',
                'statusDate' => 'required|date',
                'arrivalId' => 'required|exists:arrivals,id',
            ]);

            // Single query to get all needed data with joins
            $invoiceData = DB::table('candidates as c')
                ->join('statuses as s', 's.id', '=', DB::raw($data['status_id']))
                ->join('arrival_pricings as ap', 'ap.arrival_id', '=', DB::raw($data['arrivalId']))
                ->select(
                    'c.company_id',
                    's.nameOfStatus',
                    'ap.total as price'
                )
                ->where('c.id', $data['candidateId'])
                ->first();

            if (!$invoiceData) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Required data not found'
                ], 404);
            }

            ArrivalPricing::where('arrival_id', $data['arrivalId'])
                ->where('billed', false)
                ->update(['billed' => true]);

            $contractTypeCandidate = DB::table('candidates')
                ->where('id', $data['candidateId'])
                ->value('contractType');

            if ($contractTypeCandidate == "ЕРПР 3" || $contractTypeCandidate == "ЕРПР 2" || $contractTypeCandidate == "ЕРПР 1") {
                $agreement_type = "erpr";
            } else if ($contractTypeCandidate == "9 месеца") {
                $agreement_type = "9months";
            } else if ($contractTypeCandidate == "90 дни") {
                $agreement_type = "90days";
            }

            $company_service_contract_id = DB::table('company_service_contracts')
                ->where('company_id', $invoiceData->company_id)
                ->where('agreement_type', $agreement_type)
                ->where('status', 'active')
                ->value('id') ?? null;

            $invoice = Invoice::updateOrCreate(
                [
                    'candidate_id' => $data['candidateId'],
                    'contract_service_type_id' => config('invoice.transport_service_type_id', 5),
                    'statusName' => $invoiceData->nameOfStatus,
                ],
                [
                    'company_id' => $invoiceData->company_id,
                    'company_service_contract_id' => $company_service_contract_id ?? null,
                    'statusDate' => Carbon::parse($data['statusDate'])->format('Y-m-d'),
                    'price' => $invoiceData->price,
                    'invoiceStatus' => Invoice::INVOICE_STATUS_NOT_INVOICED,
                    'notes' => null
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Invoice processed successfully',
                'data' => ['invoice_id' => $invoice->id]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error processing arrival invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data ?? $request->all()
            ]);

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Failed to process invoice'
            ], 500);
        }
    }

    public function storeTransportCoverBy(Request $request, $arrivalId)
    {
        try {
            $arrival = Arrival::with('arrivalPricing')->where('id', $arrivalId)->firstOrFail();

            $data = $request->validate([
                'isTransportCoveredByNomad' => 'required|integer|in:0,1,2,3',
            ]);

            $data['arrival_id'] = $arrival->id;
            $data['billed'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->billed : false;
            $data['price'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->price : null;
            $data['margin'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->margin : null;
            $data['total'] = $arrival->arrivalPricing ? $arrival->arrivalPricing->total : null;

            $arrivalPricing = ArrivalPricing::updateOrCreate(
                ['arrival_id' => $arrival->id],
                $data
            );

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $arrivalPricing
            ]);
        } catch (\Exception $e) {
            Log::info('Error updating transport cover: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error updating transport cover: ' . $e->getMessage()
            ]);
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
            $data = $request->validate([
                'arrival_id' => 'required|exists:arrivals,id',
                'price' => 'required|numeric',
                'margin' => 'required|numeric',
                'total' => 'required|numeric',
            ]);

            $arrivalPricing = ArrivalPricing::updateOrCreate(
                ['arrival_id' => $data['arrival_id']],
                [
                    'price' => $data['price'],
                    'margin' => $data['margin'],
                    'total' => $data['total'],
                    'billed' => false,
                    'isTransportCoveredByNomad' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 201,
                'data' => $arrivalPricing
            ]);
        } catch (\Exception $e) {
            Log::info('Error creating arrival pricing: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error creating arrival pricing: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function show(ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function edit(ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ArrivalPricing $arrivalPricing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ArrivalPricing  $arrivalPricing
     * @return \Illuminate\Http\Response
     */
    public function destroy(ArrivalPricing $arrivalPricing)
    {
        //
    }
}
