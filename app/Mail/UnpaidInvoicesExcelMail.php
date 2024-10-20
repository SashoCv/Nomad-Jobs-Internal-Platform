<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UnpaidInvoicesExcelMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fileName;

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    public function build()
    {
        return $this->subject('Unpaid Invoices Report')
            ->markdown('unpaidInvoicesExcel')
            ->attach(storage_path('app/' . $this->fileName));
    }
}
