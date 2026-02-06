<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\EmailLog;
use App\Services\EmailTrackingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReminderEmailForExpiredContractCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:email-for-expired-contract';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder email for expired contract';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EmailTrackingService $trackingService)
    {
        // Get the date one month ago
      $oneMonthFromNow = Carbon::now()->addMonth()->format('Y-m-d');

        // Fetch candidates with expired contracts
        $allCandidatesWithThisDate = Candidate::with('company')
            ->where('contractPeriodDate', $oneMonthFromNow)
            ->get();

        // Log the number of candidates found
        $count = $allCandidatesWithThisDate->count();
        Log::info("Found {$count} candidates with expired contracts as of {$oneMonthFromNow}.");

        if ($count > 0) {
            $recipients = array_filter(array_map('trim', explode(',', config('app.nomad_notification_emails', ''))));

            if (empty($recipients)) {
                Log::warning("No recipients configured in NOMAD_NOTIFICATION_EMAILS. Skipping email.");
                return Command::SUCCESS;
            }

            // Log emails for tracking
            $logIds = $trackingService->logMultipleEmails(
                recipients: $recipients,
                subject: 'Reminder for expired contract',
                emailType: EmailLog::TYPE_CONTRACT_EXPIRY_REMINDER,
                metadata: [
                    'candidate_count' => $count,
                    'expiry_date' => $oneMonthFromNow,
                ]
            );

            try {
                Mail::send('expiredContract', ['data' => $allCandidatesWithThisDate], function ($message) use ($recipients) {
                    $message->to($recipients);
                    $message->subject('Reminder for expired contract');
                });
                $trackingService->markMultipleSent($logIds);
                Log::info("Reminder email sent successfully to " . implode(', ', $recipients) . " for {$count} expired contracts.");
            } catch (\Exception $e) {
                $trackingService->markMultipleFailed($logIds, $e->getMessage());
                Log::error("Failed to send contract expiry reminder: " . $e->getMessage());
            }
        } else {
            // Log no candidates found
            Log::info("No expired contracts found to send reminders.");
        }

        return Command::SUCCESS;
    }
}

