<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentShareMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $bodyMessage;
    public $files; // Array of file paths

    public function __construct($subjectLine, $bodyMessage, $files)
    {
        $this->subjectLine = $subjectLine;
        $this->bodyMessage = $bodyMessage;
        $this->files = $files;
    }

    public function build()
    {
        $email = $this->subject($this->subjectLine)
            ->view('emails.documentShare');

        foreach ($this->files as $file) {
            if (file_exists($file)) {
                 $email->attach($file);
            }
        }

        return $email;
    }
}
