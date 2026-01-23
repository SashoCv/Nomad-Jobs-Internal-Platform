<?php

namespace App\Jobs;

use App\Mail\WelcomeSetPasswordMail;
use App\Models\EmailLog;
use App\Services\EmailTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeSetPasswordEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $email;
    protected string $setPasswordUrl;
    protected string $userName;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $setPasswordUrl
     * @param string $userName
     */
    public function __construct(string $email, string $setPasswordUrl, string $userName)
    {
        $this->email = $email;
        $this->setPasswordUrl = $setPasswordUrl;
        $this->userName = $userName;
        $this->onQueue('mail');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EmailTrackingService $trackingService): void
    {
        Log::info("SendWelcomeSetPasswordEmailJob: Sending welcome email to: " . $this->email);

        $logId = $trackingService->logEmail(
            recipientEmail: $this->email,
            subject: 'Welcome to Nomad Cloud - Set Your Password',
            emailType: EmailLog::TYPE_WELCOME_SET_PASSWORD,
            recipientName: $this->userName,
            metadata: ['user_name' => $this->userName]
        );

        try {
            Mail::to($this->email)->send(new WelcomeSetPasswordMail($this->setPasswordUrl, $this->userName));
            $trackingService->markSent($logId);
            Log::info("SendWelcomeSetPasswordEmailJob: Welcome email sent successfully to " . $this->email);
        } catch (\Exception $e) {
            $trackingService->markFailed($logId, $e->getMessage());
            Log::error("SendWelcomeSetPasswordEmailJob: Error sending welcome email: " . $e->getMessage());
            throw $e;
        }
    }
}
