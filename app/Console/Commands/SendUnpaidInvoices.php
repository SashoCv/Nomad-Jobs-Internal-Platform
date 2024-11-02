<?php

namespace App\Console\Commands;

use App\Jobs\UnpaidInvoicesExcelMailJob;
use App\Models\InvoiceCompany;
use App\Models\InvoiceCompanyCandidate;
use App\Models\ItemsForInvoices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
        $currentYear = Carbon::now()->year;

        $dateFrom = Carbon::create($currentYear, 1, 1);
        $dateTo = Carbon::create($currentYear, 12, 31);

        $invoices = InvoiceCompanyCandidate::with('candidate', 'invoiceCompany', 'invoiceCompany.company', 'invoiceCompany.itemInvoice')
            ->whereHas('invoiceCompany', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo]);
            })
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
                'Invoice Date' => $invoice->invoiceCompany->invoice_date,
                'Status' => $invoice->invoiceCompany->status,
                'Invoice Amount' => $invoice->invoiceCompany->invoice_amount,
                'Payment Amount' => $invoice->invoiceCompany->payment_amount,
                'Items' => $invoiceItems,
            ];
        }

        $fileName = 'unpaid_invoices_' . Carbon::now()->format('Y-m-d') . '.xlsx';
        $filePath = storage_path("app/{$fileName}");

        // Зачувување на Excel датотеката
        Excel::store(new InvoicesExport($data), $fileName, 'local');

        // Логирање на патеката на зачуваната датотека
        Log::info('Unpaid invoices exported successfully.', ['file_path' => $filePath]);

        // Испраќање на мејлот со пратена датотека
        dispatch(new UnpaidInvoicesExcelMailJob($fileName));

        Storage::delete($fileName);

        $this->info('Unpaid invoices sent successfully.');
    }
}

