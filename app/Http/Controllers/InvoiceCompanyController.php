<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\InvoiceCompany;
use App\Models\ItemInvoice;
use App\Models\UserOwner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {
                $query = InvoiceCompany::with(['itemInvoice' => function ($query) {
                    $query->select('id', 'invoice_companies_id', 'item_name', 'quantity', 'price', 'total', 'unit');
                }]);

                $query->whereBetween('invoice_date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);

                if ($request->company_id) {
                    $query->where('company_id', $request->company_id);
                }

                if ($request->is_paid) {
                    $query->where('is_paid', $request->is_paid);
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

                $invoicesForCompany = $query->orderBy('invoice_date', 'desc')->get();

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
            if(Auth::user()->role_id == "1" || Auth::user()->role_id == "2") {

                $invoiceCompany = new InvoiceCompany();

                $invoiceCompany->company_id = $request->company_id;
                $invoiceCompany->invoice_number = $request->invoice_number;
                $invoiceCompany->invoice_date = $request->invoice_date;
                $invoiceCompany->status = $request->status;
                $invoiceCompany->invoice_amount = $request->invoice_amount;
                $invoiceCompany->due_date = $request->due_date;
                $invoiceCompany->payment_date = $request->payment_date;
                $invoiceCompany->payment_amount = $request->payment_amount;
                $invoiceCompany->is_paid = $request->is_paid;

                if ($invoiceCompany->save()) {
                    $items = $request->items;

                    if(!$items) {
                        return response()->json('Items are required');
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

    public function destroy($id): JsonResponse
    {
        try {
            if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

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
            if(Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json('You are not authorized to perform this action');
            }

            $invoiceCompany = InvoiceCompany::find($id);

            $invoiceCompany->is_paid = true;
            $invoiceCompany->payment_date = $request->payment_date;

            if($invoiceCompany->save()) {
                return response()->json([
                    'message' => 'Invoice paid successfully',
                    'invoice' => $invoiceCompany
                ]);
            } else {
                return response()->json('Invoice was not paid');
            }

        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
