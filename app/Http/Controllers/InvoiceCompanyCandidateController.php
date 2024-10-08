<?php

namespace App\Http\Controllers;

use App\Http\Transformers\TransformInvoiceCompanyCandidates;
use App\Models\InvoiceCompanyCandidate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceCompanyCandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Fetch filters from the request
            $filters = request()->only(['is_paid', 'invoice_number', 'company_id', 'candidate_id', 'dateFrom', 'dateTo']);

            // Query the InvoiceCompanyCandidate model with related models
            $invoiceCompanyCandidates = InvoiceCompanyCandidate::with([
                'invoiceCompany' => function ($query) {
                    $query->select('id', 'company_id', 'invoice_number', 'invoice_date', 'status', 'invoice_amount', 'payment_date', 'payment_amount', 'is_paid')
                        ->with([
                            'company' => function ($query) {
                                $query->select('id', 'nameOfCompany'); // Select only required columns
                            },
                            'itemInvoice' => function ($query) {
                                $query->select('id', 'invoice_companies_id', 'items_for_invoices_id', 'price', 'percentage', 'amount', 'total')
                                    ->with([
                                        'itemForInvoice' => function ($query) {
                                            $query->select('id', 'name'); // Select only required columns
                                        }
                                    ]);
                            }
                        ]);
                },
                'candidate' => function ($query) {
                    $query->select('id', 'fullName'); // Select only required columns
                }
            ])
                ->select('id', 'candidate_id', 'invoice_company_id') // Select only required columns from the main model
                ->when(isset($filters['is_paid']), function ($query) use ($filters) {
                    return $query->whereHas('invoiceCompany', function ($subQuery) use ($filters) {
                        $subQuery->where('is_paid', $filters['is_paid'] === "true" ? 1 : 0);
                    });
                })
                ->when(isset($filters['company_id']), function ($query) use ($filters) {
                    return $query->whereHas('invoiceCompany', function ($subQuery) use ($filters) {
                        $subQuery->where('company_id', $filters['company_id']);
                    });
                })
                ->when(isset($filters['candidate_id']), function ($query) use ($filters) {
                    return $query->where('candidate_id', $filters['candidate_id']);
                })
                ->when(isset($filters['dateFrom']) && isset($filters['dateTo']), function ($query) use ($filters) {
                    return $query->whereHas('invoiceCompany', function ($subQuery) use ($filters) {
                        $subQuery->whereBetween('invoice_date', [$filters['dateFrom'], $filters['dateTo']]);
                    });
                })
                ->whereHas('invoiceCompany') // Ensure that the invoice company is not null
                ->orderBy('id', 'desc')
                ->paginate(15);

            // Transform the collection for the response
            $invoiceCompanyCandidates->getCollection()->transform(function ($invoice) {
                // Format dates inside the invoice_company object
                if ($invoice->invoiceCompany) {
                    $invoice->invoiceCompany->invoice_date = Carbon::parse($invoice->invoiceCompany->invoice_date)->format('m-d-Y');
                    $invoice->invoiceCompany->payment_date = Carbon::parse($invoice->invoiceCompany->payment_date)->format('m-d-Y');
                    $invoice->invoiceCompany->is_paid = (bool) $invoice->invoiceCompany->is_paid; // Convert to boolean
                }

                return $invoice;
            });

            // Return the transformed data as JSON
            return response()->json($invoiceCompanyCandidates);

        } catch (\Exception $e) {
            Log::error($e->getMessage()); // Use Log::error for exceptions
            return response()->json(['error' => 'Error fetching invoice company candidates'], 500); // Return 500 status code
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InvoiceCompanyCandidate  $invoiceCompanyCandidate
     * @return \Illuminate\Http\Response
     */
    public function show(InvoiceCompanyCandidate $invoiceCompanyCandidate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InvoiceCompanyCandidate  $invoiceCompanyCandidate
     * @return \Illuminate\Http\Response
     */
    public function edit(InvoiceCompanyCandidate $invoiceCompanyCandidate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InvoiceCompanyCandidate  $invoiceCompanyCandidate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InvoiceCompanyCandidate $invoiceCompanyCandidate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InvoiceCompanyCandidate  $invoiceCompanyCandidate
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvoiceCompanyCandidate $invoiceCompanyCandidate)
    {
        //
    }
}
