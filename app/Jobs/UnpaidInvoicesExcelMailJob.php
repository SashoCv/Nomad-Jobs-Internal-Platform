<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UnpaidInvoicesExcelMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $fileName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->onQueue('mail');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Sending email with unpaid invoices report");
        Log::info("File name: " . $this->fileName);
        try {
            Mail::send('excelWithUnpaidInvoices', [], function ($message)  {
                $message->to('sasocvetanoski@gmail.com')
                    ->attach(storage_path('app/' . $this->fileName))
                    ->subject('Unpaid Invoices Report');
            });

            Log::info("Email sent successfully");
        } catch (\Exception $e) {
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
