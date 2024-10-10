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
    public function index(Request $request)
    {
        try {
            $filters = $request->all();

            if (!empty($filters['dateFrom']) && !empty($filters['dateTo'])) {
                // Ensure the date format is correct before parsing
                try {
                    $filters['dateFrom'] = Carbon::createFromFormat('m-d-Y', $filters['dateFrom'])->format('Y-m-d');
                    $filters['dateTo'] = Carbon::createFromFormat('m-d-Y', $filters['dateTo'])->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::info('Invalid date format. Use m-d-Y format');
                    return response()->json(['error' => 'Invalid date format. Use m-d-Y format'], 400);
                }
            }


            $invoiceCompanyCandidates = InvoiceCompanyCandidate::with([
                'invoiceCompany' => function ($query) {
                    $query->select('id', 'company_id', 'invoice_number', 'invoice_date', 'status', 'invoice_amount', 'payment_date', 'payment_amount', 'is_paid')
                        ->with([
                            'itemInvoice' => function ($query) {
                                $query->select('id', 'invoice_companies_id', 'items_for_invoices_id', 'price', 'percentage', 'amount', 'total');
                            }
                        ]);
                },
                'candidate' => function ($query) {
                    $query->select('id', 'fullNameCyrillic', 'periodOfResidence'); // Select only required columns
                }
            ])
                ->select('id', 'candidate_id', 'invoice_company_id') // Select only required columns from the main model
                ->when(isset($filters['is_paid']), function ($query) use ($request) {
                    return $query->whereHas('invoiceCompany', function ($subQuery) use ($request) {
                        $subQuery->where('is_paid', $request->is_paid === "true" ? 1 : 0);
                    });
                })
                ->when(isset($filters['company_id']), function ($query) use ($filters) {
                    return $query->whereHas('invoiceCompany', function ($subQuery) use ($filters) {
                        $subQuery->where('company_id', (int) $filters['company_id']);
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
                ->whereHas('invoiceCompany')
                ->orderBy('id', 'desc')
                ->paginate(15);

            $invoiceCompanyCandidates->getCollection()->transform(function ($invoice) {
                if ($invoice->invoiceCompany) {
                    $invoice->invoiceCompany->invoice_date = $invoice->invoiceCompany->invoice_date ?
                        Carbon::parse($invoice->invoiceCompany->invoice_date)->format('m-d-Y') : null;
                    $invoice->invoiceCompany->payment_date = $invoice->invoiceCompany->payment_date ?
                        Carbon::parse($invoice->invoiceCompany->payment_date)->format('m-d-Y') : null;

                    $invoice->invoiceCompany->is_paid = (bool) $invoice->invoiceCompany->is_paid;
                }

                return $invoice;
            });

            return response()->json($invoiceCompanyCandidates);

        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error fetching invoice company candidates'], 500); // Return 500 status code
        }
    }



    public function filterAutoCompleteCandidateThatHaveInvoice(Request $request)
    {
        try {
            $fetchAllCandidates = InvoiceCompanyCandidate::with(['candidate:id,fullName,fullNameCyrillic,company_id'])
                ->whereHas('candidate', function ($query) use ($request) {
                    $searchName = $request->input('searchName');
                    $companyId = $request->input('company_id'); // земи го company_id од барањето

                    $query->where(function ($q) use ($searchName) {
                        $q->where('fullName', 'like', '%' . $searchName . '%')
                            ->orWhere('fullNameCyrillic', 'like', '%' . $searchName . '%');
                    });

                    if ($companyId) {
                        $query->where('company_id', $companyId); // додади филтер за company_id
                    }
                })
                ->get();

            return response()->json($fetchAllCandidates);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error fetching candidates'], 500);
        }
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Fetch the invoice company candidate with related models
            $invoiceCompanyCandidate = InvoiceCompanyCandidate::with([
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
                ->where('id', $id)
                ->first();

            // Check if record was found
            if (!$invoiceCompanyCandidate) {
                return response()->json(['error' => 'Invoice company candidate not found'], 404); // Return 404 if not found
            }

            // Format dates inside the invoiceCompany object
            if ($invoiceCompanyCandidate->invoiceCompany) {
                $invoiceCompanyCandidate->invoiceCompany->invoice_date = Carbon::parse($invoiceCompanyCandidate->invoiceCompany->invoice_date)->format('m-d-Y');
                $invoiceCompanyCandidate->invoiceCompany->payment_date = Carbon::parse($invoiceCompanyCandidate->invoiceCompany->payment_date)->format('m-d-Y');
                $invoiceCompanyCandidate->invoiceCompany->is_paid = (bool) $invoiceCompanyCandidate->invoiceCompany->is_paid; // Convert to boolean
            }

            // Return the transformed data as JSON
            return response()->json($invoiceCompanyCandidate);

        } catch (\Exception $e) {
            Log::error($e->getMessage()); // Log the error message
            return response()->json(['error' => 'Error fetching invoice company candidate'], 500); // Return 500 status code
        }
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
       try{
            $invoiceCompanyCandidate = InvoiceCompanyCandidate::find($id);
            $invoiceCompanyCandidate->delete();
            return response()->json([
                'message' => 'Invoice Company Candidate deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error deleting invoice company candidate'], 500);
       }
    }
}
