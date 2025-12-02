<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeSetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setPasswordUrl;
    public string $userName;

    /**
     * Create a new message instance.
     *
     * @param string $setPasswordUrl
     * @param string $userName
     */
    public function __construct(string $setPasswordUrl, string $userName)
    {
        $this->setPasswordUrl = $setPasswordUrl;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to Nomad Cloud - Set Your Password')
            ->view('emails.welcome-set-password');
    }
}
