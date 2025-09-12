<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Permission;
use App\Http\Transformers\TransformInvoice;
use App\Traits\HasRolePermissions;
use App\Exports\InvoicesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission(Permission::FINANCE_READ)) {
            return response()->json(['error' => 'Access denied', 'message' => 'You do not have permission to view invoices'], 403);
        }

        try {
            $query = Invoice::with(['candidate', 'company', 'companyServiceContract', 'contractServiceType']);

            // Filter by candidate name (like) - search both Latin and Cyrillic names
            if ($request->filled('candidateName')) {
                $query->whereHas('candidate', function ($q) use ($request) {
                    $q->where(function ($subQuery) use ($request) {
                        $subQuery->where('fullName', 'like', '%' . $request->candidateName . '%')
                                 ->orWhere('fullNameCyrillic', 'like', '%' . $request->candidateName . '%');
                    });
                });
            }

            // Filter by company name (like)
            if ($request->filled('companyName')) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('nameOfCompany', 'like', '%' . $request->companyName . '%');
                });
            }

            // Filter by invoice status
            if ($request->filled('invoiceStatus')) {
                $query->where('invoiceStatus', $request->invoiceStatus);
            }

            // Filter by date range
            if ($request->filled('dateFrom')) {
                $query->where('statusDate', '>=', $request->dateFrom);
            }

            if ($request->filled('dateTo')) {
                $query->where('statusDate', '<=', $request->dateTo);
            }

            // Get all filtered invoices for summary calculation
            $allFilteredInvoices = $query->get();

            $invoices = $query->paginate($request->get('per_page', 15));

            $transformer = new TransformInvoice();
            $transformedData = $transformer->transform($invoices, $allFilteredInvoices);

            return response()->json($transformedData);
        } catch (\Exception $e) {
            Log::info('Error retrieving invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve invoices', 'message' => $e->getMessage()], 500);
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
    public function store(Request $request, $id)
    {
        if (!$this->checkPermission(Permission::FINANCE_UPDATE)) {
            return response()->json(['error' => 'Access denied', 'message' => 'You do not have permission to update invoices'], 403);
        }

        try {
            $invoice = Invoice::find($id);
            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            $invoice->invoiceStatus = $request->invoiceStatus;
            $invoice->notes = $request->notes;
            $invoice->save();

            return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice]);
        } catch (\Exception $e) {
            Log::info('Error updating invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!$this->checkPermission(Permission::FINANCE_DELETE)) {
            return response()->json(['error' => 'Access denied', 'message' => 'You do not have permission to delete invoices'], 403);
        }

        try {
            $invoice = Invoice::find($id);
            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }
            $invoice->delete();
            return response()->json(['message' => 'Invoice deleted successfully']);
        } catch (\Exception $e) {
            Log::info('Error deleting invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export invoices to Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportInvoices(Request $request)
    {
        if (!$this->checkPermission(Permission::FINANCE_READ)) {
            return response()->json(['error' => 'Access denied', 'message' => 'You do not have permission to export invoices'], 403);
        }

        try {
            $query = Invoice::with(['candidate', 'company', 'companyServiceContract', 'contractServiceType']);

            // Apply the same filters as the index method
            if ($request->filled('candidateName')) {
                $query->whereHas('candidate', function ($q) use ($request) {
                    $q->where(function ($subQuery) use ($request) {
                        $subQuery->where('fullName', 'like', '%' . $request->candidateName . '%')
                                 ->orWhere('fullNameCyrillic', 'like', '%' . $request->candidateName . '%');
                    });
                });
            }

            if ($request->filled('companyName')) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('nameOfCompany', 'like', '%' . $request->companyName . '%');
                });
            }

            if ($request->filled('invoiceStatus')) {
                $query->where('invoiceStatus', $request->invoiceStatus);
            }

            if ($request->filled('dateFrom')) {
                $query->where('statusDate', '>=', $request->dateFrom);
            }

            if ($request->filled('dateTo')) {
                $query->where('statusDate', '<=', $request->dateTo);
            }

            $invoices = $query->get();

            $export = new InvoicesExport($invoices);
            $currentDate = Carbon::now()->format('d-m-Y');
            
            return Excel::download($export, 'invoices_export_' . $currentDate . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Error exporting invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to export invoices', 'message' => $e->getMessage()], 500);
        }
    }
}
