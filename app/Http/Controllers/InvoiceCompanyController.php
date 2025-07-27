<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesExport;
use App\Traits\HasRolePermissions;
use App\Models\Company;
use App\Models\InvoiceCompany;
use App\Models\InvoiceCompanyCandidate;
use App\Models\ItemInvoice;
use App\Models\ItemsForInvoices;
use App\Models\UserOwner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;

class InvoiceCompanyController extends Controller
{
    use HasRolePermissions;
    protected $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }
    public function index(Request $request): JsonResponse
    {
        try {
            if ($this->isStaff()) {
                $query = InvoiceCompany::with([
                    'company' => function ($query) {
                        $query->select('id', 'nameOfCompany');
                    },
                    'itemInvoice' => function ($query) {
                        $query->select('id', 'invoice_companies_id', 'item_name', 'quantity', 'price', 'total', 'unit');
                    }
                ]);

                $query->whereBetween('invoice_date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);

                if ($request->company_id) {
                    $query->where('company_id', $request->company_id);
                }

                if ($request->is_paid === "true") {
                    $query->where('is_paid', 1);
                }

                if($request->is_paid === "false"){
                    $query->where('is_paid', 0);
                }

                if ($request->status) {
                    $query->where('status', $request->status);
                }

                if ($request->payment_date) {
                    $paymentDate = Carbon::parse($request->payment_date)->format('Y-m-d');
                    $query->where('payment_date', $paymentDate);
                }

                if ($request->due_date) {
                    $dueDate = Carbon::parse($request->due_date)->format('Y-m-d');
                    $query->where('due_date', $dueDate);
                }

                if ($request->monthFrom && $request->monthTo) {
                    $monthFrom = Carbon::parse($request->monthFrom)->format('Y-m-d');
                    $monthTo = Carbon::parse($request->monthTo)->format('Y-m-d');
                    $query->whereBetween('invoice_date', [$monthFrom, $monthTo]);
                }

                $perPage = $request->get('per_page', 15);
                $invoicesForCompany = $query->orderBy('id', 'desc')->paginate($perPage);

                $invoicesForCompany->getCollection()->transform(function ($invoice) {
                    $invoice->due_date = Carbon::parse($invoice->due_date)->format('m-d-Y');
                    $invoice->invoice_date = Carbon::parse($invoice->invoice_date)->format('m-d-Y');
                    $invoice->payment_date = Carbon::parse($invoice->payment_date)->format('m-d-Y');
                    $invoice->is_paid = $invoice->is_paid == 1 ? true : false;
                    return $invoice;
                });

                return response()->json($invoicesForCompany);
            } else {
                return response()->json('You are not authorized to perform this action');
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if (Auth::user()->role_id == "1" || Auth::user()->role_id == "2") {

                $invoiceCompany = new InvoiceCompany();

                $invoiceCompany->company_id = $request->company_id;
                $invoiceCompany->invoice_number = $request->invoice_number;
                $invoiceCompany->invoice_date = Carbon::createFromFormat('m-d-Y', $request->invoice_date)->format('Y-m-d');
                $invoiceCompany->invoice_amount = $request->invoice_amount;
                $invoiceCompany->payment_date = Carbon::createFromFormat('m-d-Y',$request->payment_date)->format('Y-m-d');
                $invoiceCompany->payment_amount = $request->payment_amount;
                $invoiceCompany->is_paid = $request->is_paid;
                $invoiceCompany->notes = $request->notes;
                $invoiceCompany->type = $request->type; // TYPE: companyInvoice, 2 cashInvoice, 3 agentInvoice
                $items = $request->items;
                $candidate_id = $request->candidate_id;
                $invoiceCompany->agent_id = $request->agent_id ?? null;

                if($request->is_paid == 1){
                    $isPaid = true;
                } else {
                    $isPaid = false;
                }


                if (!$items) {
                    return response()->json('Items are required');
                }

                if($isPaid){
                    $invoiceCompany->status = 'Paid';
                } else {
                    $invoiceCompany->status = 'Unpaid';
                }

                if ($invoiceCompany->save()) {

                    foreach ($items as $item) {
                        $itemInvoice = new ItemInvoice();
                        $itemInvoice->invoice_companies_id = $invoiceCompany->id;
                        $itemInvoice->price = $item['price'];
                        $itemInvoice->total = $item['total'];
                        $itemInvoice->percentage = $item['percentage'];
                        $itemInvoice->amount = $item['amount'];
                        $itemInvoice->items_for_invoices_id = $item['items_for_invoices_id'];


                        $itemInvoice->save();
                    }

                    $invoiceCompanyCandidates = new InvoiceCompanyCandidate();
                    $invoiceCompanyCandidates->invoice_company_id = $invoiceCompany->id;
                    $invoiceCompanyCandidates->candidate_id = $candidate_id;
                    $invoiceCompanyCandidates->save();

                    $itemInvoice = ItemInvoice::where('invoice_companies_id', $invoiceCompany->id)->get();

                    $invoiceCompany->items = $itemInvoice;
                    $invoiceCompany->candidate_id = $candidate_id;

                    if($invoiceCompany->is_paid == 1){
                        $invoiceCompany->is_paid = true;
                    } else {
                        $invoiceCompany->is_paid = false;
                    }

                    return response()->json([
                        'message' => 'Invoice saved successfully',
                        'invoice' => $invoiceCompany
                    ]);
                } else {
                    return response()->json('Invoice was not saved');
                }
            } else {
                return response()->json('You are not authorized to perform this action');
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $invoiceCompany = InvoiceCompany::find($id);

            $invoiceCompany->company_id = $request->company_id;
            $invoiceCompany->invoice_number = $request->invoice_number;
            $invoiceCompany->invoice_date = Carbon::createFromFormat('m-d-Y',$request->invoice_date)->format('Y-m-d');
            $invoiceCompany->invoice_amount = $request->invoice_amount;
            $invoiceCompany->payment_date = Carbon::createFromFormat('m-d-Y',$request->payment_date)->format('Y-m-d');
            $invoiceCompany->payment_amount = $request->payment_amount;
            $invoiceCompany->is_paid = $request->is_paid;
            $invoiceCompany->notes = $request->notes;
            $invoiceCompany->type = $request->type; // TYPE: companyInvoice, 2 cashInvoice, 3 agentInvoice
            $items = $request->items;
            $invoiceCompany->agent_id = $request->agent_id ?? null;

            if (!$items) {
                return response()->json('Items are required');
            }

            if($request->is_paid == 1){
                $isPaid = true;
            } else {
                $isPaid = false;
            }

            if($isPaid){
                $invoiceCompany->status = 'Paid';
            } else {
                $invoiceCompany->status = 'Unpaid';
            }

            if ($invoiceCompany->save()) {

                $itemInvoice = ItemInvoice::where('invoice_companies_id', $id)->get();

                if ($itemInvoice) {
                    foreach ($itemInvoice as $item) {
                        $item->delete();
                    }
                }

                foreach ($items as $item) {
                    $itemInvoice = new ItemInvoice();
                    $itemInvoice->invoice_companies_id = $invoiceCompany->id;
                    $itemInvoice->price = $item['price'];
                    $itemInvoice->total = $item['total'];
                    $itemInvoice->percentage = $item['percentage'];
                    $itemInvoice->amount = $item['amount'];
                    $itemInvoice->items_for_invoices_id = $item['items_for_invoices_id'];

                    $itemInvoice->save();
                }


                $invoiceCompanyCandidate = InvoiceCompanyCandidate::where('invoice_company_id', $id)->first();
                $invoiceCompanyCandidate->candidate_id = $request->candidate_id;
                $invoiceCompanyCandidate->save();

                $itemInvoice = ItemInvoice::where('invoice_companies_id', $id)->get();
                $invoiceCompany->items = $itemInvoice;
                $invoiceCompany->candidate_id = $request->candidate_id;

                return response()->json([
                    'message' => 'Invoice updated successfully',
                    'invoice' => $invoiceCompany
                ]);
            } else {
                return response()->json('Invoice was not updated');
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }


    public function show($id): JsonResponse
    {
        try {
            $invoiceCompany = InvoiceCompany::with([
                'company' => function ($query) {
                    $query->select('id', 'nameOfCompany');
                },
                'itemInvoice' => function ($query) {
                    $query->select('id', 'invoice_companies_id','items_for_invoices_id', 'price', 'percentage', 'amount',  'total');
                }
            ])->find($id);

            if($invoiceCompany->is_paid == 1){
                $invoiceCompany->is_paid = true;
            } else {
                $invoiceCompany->is_paid = false;
            }

            $invoiceCompany->getCollection()->transform(function ($invoice) {
                $invoice->due_date = Carbon::parse($invoice->due_date)->format('m-d-Y');
                $invoice->invoice_date = Carbon::parse($invoice->invoice_date)->format('m-d-Y');
                $invoice->payment_date = Carbon::parse($invoice->payment_date)->format('m-d-Y');
                $invoice->is_paid = $invoice->is_paid == 1 ? true : false;
                return $invoice;
            });

            return response()->json($invoiceCompany);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            if ($this->isStaff()) {

                $invoiceCompany = InvoiceCompany::find($id);
                $itemInvoice = ItemInvoice::where('invoice_companies_id', $id)->get();

                if ($itemInvoice) {
                    foreach ($itemInvoice as $item) {
                        $item->delete();
                    }
                }
                if ($invoiceCompany->delete()) {
                    return response()->json('Invoice deleted successfully');
                } else {
                    return response()->json('Invoice was not deleted');
                }
            } else {
                return response()->json('You are not authorized to perform this action');
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function invoicePaid(Request $request, $id): JsonResponse
    {
        try {
            if ($this->isStaff()) {

                $invoiceCompany = InvoiceCompany::find($id);

                $invoiceCompany->is_paid = true;
                $invoiceCompany->payment_date = $request->payment_date;
                $invoiceCompany->status = 'Paid';

                if ($invoiceCompany->save()) {
                    return response()->json([
                        'message' => 'Invoice paid successfully',
                        'invoice' => $invoiceCompany
                    ]);
                } else {
                    return response()->json('Invoice was not paid');
                }
            } else {
                return response()->json('You are not authorized to perform this action');
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function downloadExcelForInvoices(Request $request)
    {
        try {
            if ($this->isStaff()) {

                $invoices = InvoiceCompanyCandidate::with('candidate', 'invoiceCompany', 'invoiceCompany.company', 'invoiceCompany.itemInvoice')
                    ->get();

                $data = [];
                foreach ($invoices as $invoice) {
                    $invoiceItems = [];

                    foreach ($invoice->invoiceCompany->itemInvoice as $item) {
                        $itemName = ItemsForInvoices::where('id', $item->items_for_invoices_id)->first();

                        $invoiceItems[] = [
                            'Item Name' => $itemName->name,
                            'Total' => $item->total,
                            'Percentage' => $item->percentage,
                        ];
                    }
                    $data[] = [
                        'Company Name' => $invoice->invoiceCompany->company->nameOfCompany,
                        'candidate' => $invoice->candidate->fullNameCyrillic,
                        'Invoice Number' => $invoice->invoiceCompany->invoice_number,
                        'Invoice Date' => Carbon::parse($invoice->invoiceCompany->invoice_date)->format('d-m-Y'),
                        'Status' => $invoice->invoiceCompany->status,
                        'Invoice Amount' => $invoice->invoiceCompany->invoice_amount,
                        'Payment Amount' => $invoice->invoiceCompany->payment_amount,
                        'Items' => $invoiceItems,
                    ];
                }


                $fileName = 'invoices_' . Carbon::now()->format('Y-m-d') . '.xlsx';

                return $this->excel->download(new InvoicesExport($data), $fileName);
            } else {
                return response()->json('You are not authorized to perform this action');
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
