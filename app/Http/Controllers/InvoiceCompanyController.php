<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesExport;
use App\Models\Company;
use App\Models\InvoiceCompany;
use App\Models\ItemInvoice;
use App\Models\UserOwner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;

class InvoiceCompanyController extends Controller
{
    protected $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }
    public function index(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
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

                if ($request->is_paid == true) {
                    $query->where('is_paid', '=', 1);
                }

                if ($request->is_paid == false) {
                    $query->where('is_paid', '=', 0);
                }

                if ($request->status) {
                    $query->where('status', $request->status);
                }

                if ($request->payment_date) {
                    $query->where('payment_date', $request->payment_date);
                }

                if ($request->due_date) {
                    $query->where('due_date', $request->due_date);
                }

                if ($request->monthFrom && $request->monthTo) {
                    $query->whereBetween('invoice_date', [$request->monthFrom, $request->monthTo]);
                }

                $perPage = $request->get('per_page', 15);
                $invoicesForCompany = $query->orderBy('invoice_date', 'desc')->paginate($perPage);


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
                $invoiceCompany->invoice_date = $request->invoice_date;
                $invoiceCompany->invoice_amount = $request->invoice_amount;
                $invoiceCompany->due_date = $request->due_date;
                $invoiceCompany->payment_date = $request->payment_date;
                $invoiceCompany->payment_amount = $request->payment_amount;
                $invoiceCompany->is_paid = $request->is_paid;
                $items = $request->items;

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
                        $itemInvoice->item_name = $item['item_name'];
                        $itemInvoice->quantity = $item['quantity'];
                        $itemInvoice->price = $item['price'];
                        $itemInvoice->total = $item['total'];
                        $itemInvoice->unit = $item['unit'];

                        $itemInvoice->save();
                    }

                    $invoiceCompany->itemInvoice = $itemInvoice;

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
            $invoiceCompany->invoice_date = $request->invoice_date;
            $invoiceCompany->status = $request->status;
            $invoiceCompany->invoice_amount = $request->invoice_amount;
            $invoiceCompany->due_date = $request->due_date;
            $invoiceCompany->payment_date = $request->payment_date;
            $invoiceCompany->payment_amount = $request->payment_amount;
            $invoiceCompany->is_paid = $request->is_paid;
            $items = $request->items;

            if (!$items) {
                return response()->json('Items are required');
            }

            if($request->is_paid){
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
                    $itemInvoice->item_name = $item['item_name'];
                    $itemInvoice->quantity = $item['quantity'];
                    $itemInvoice->price = $item['price'];
                    $itemInvoice->total = $item['total'];
                    $itemInvoice->unit = $item['unit'];

                    $itemInvoice->save();
                }

                $invoiceCompany->itemInvoice = $itemInvoice;

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
                    $query->select('id', 'invoice_companies_id', 'item_name', 'quantity', 'price', 'total', 'unit');
                }
            ])->find($id);

            if($invoiceCompany->is_paid == 1){
                $invoiceCompany->is_paid = true;
            } else {
                $invoiceCompany->is_paid = false;
            }

            return response()->json($invoiceCompany);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

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
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

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
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $currentYear = Carbon::now()->year;

                $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::create($currentYear, 1, 1);
                $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::create($currentYear, 12, 31);

                $invoices = InvoiceCompany::with([
                    'company' => function ($query) {
                        $query->select('id', 'nameOfCompany');
                    },
                    'itemInvoice' => function ($query) {
                        $query->select('id', 'invoice_companies_id', 'item_name', 'quantity', 'price', 'total', 'unit');
                    }
                ])
                    ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->get();

                $data = [];
                foreach ($invoices as $invoice) {
                    $invoiceItems = [];

                    foreach ($invoice->itemInvoice as $item) {
                        $invoiceItems[] = [
                            'Item Name' => $item->item_name,
                            'Quantity' => $item->quantity,
                            'Price' => $item->price,
                            'Total' => $item->total,
                            'Unit' => $item->unit,
                        ];
                    }

                    $data[] = [
                        'Company Name' => $invoice->company->nameOfCompany,
                        'Invoice Number' => $invoice->invoice_number,
                        'Invoice Date' => $invoice->invoice_date,
                        'Status' => $invoice->status,
                        'Invoice Amount' => $invoice->invoice_amount,
                        'Due Date' => $invoice->due_date,
                        'Payment Date' => $invoice->payment_date,
                        'Payment Amount' => $invoice->payment_amount,
                        'Is Paid' => $invoice->is_paid,
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
