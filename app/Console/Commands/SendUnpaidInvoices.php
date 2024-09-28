<?php

namespace App\Console\Commands;

use App\Models\InvoiceCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoicesExport;
use App\Mail\UnpaidInvoicesExcelMail;
use Illuminate\Support\Facades\Storage;

class SendUnpaidInvoices extends Command
{
    protected $signature = 'invoices:send-unpaid';

    protected $description = 'Send unpaid invoices every Friday';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $unpaidInvoices = InvoiceCompany::with([
            'company' => function ($query) {
                $query->select('id', 'nameOfCompany');
            },
            'itemInvoice' => function ($query) {
                $query->select('id', 'invoice_companies_id', 'item_name', 'quantity', 'price', 'total', 'unit');
            }
        ])->where('is_paid', 0)->get();

        if ($unpaidInvoices->isEmpty()) {
            $this->info('No unpaid invoices found.');
            return;
        }

        $data = [];
        foreach ($unpaidInvoices as $invoice) {
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

        $fileName = 'unpaid_invoices_' . Carbon::now()->format('Y-m-d') . '.xlsx';
        Excel::store(new InvoicesExport($data), $fileName, 'local');

        Mail::to('sasocvetanoski@gmail.com')->send(new UnpaidInvoicesExcelMail($fileName)); // Change this email address

        Storage::delete($fileName);

        $this->info('Unpaid invoices sent successfully.');
    }
}

