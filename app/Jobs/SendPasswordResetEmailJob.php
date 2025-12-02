<?php

namespace App\Jobs;

use App\Mail\PasswordResetMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $email;
    protected string $resetUrl;
    protected string $userName;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $resetUrl
     * @param string $userName
     */
    public function __construct(string $email, string $resetUrl, string $userName)
    {
        $this->email = $email;
        $this->resetUrl = $resetUrl;
        $this->userName = $userName;
        $this->onQueue('mail');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info("SendPasswordResetEmailJob: Sending password reset email to: " . $this->email);

        try {
            Mail::to($this->email)->send(new PasswordResetMail($this->resetUrl, $this->userName));
            Log::info("SendPasswordResetEmailJob: Password reset email sent successfully to " . $this->email);
        } catch (\Exception $e) {
            Log::error("SendPasswordResetEmailJob: Error sending password reset email: " . $e->getMessage());
            throw $e;
        }
    }
}
