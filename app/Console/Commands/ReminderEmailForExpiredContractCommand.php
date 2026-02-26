<?php

namespace App\Console\Commands;

use App\Models\CandidateContract;
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
    protected $description = 'Send reminder email for contracts expiring in one month';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EmailTrackingService $trackingService)
    {
        $oneMonthFromNow = Carbon::now()->addMonth()->format('Y-m-d');

        // Query active contracts expiring in exactly one month
        $expiringContracts = CandidateContract::with(['candidate.company'])
            ->where('is_active', true)
            ->whereDate('end_contract_date', $oneMonthFromNow)
            ->get();

        $count = $expiringContracts->count();
        Log::info("Found {$count} contracts expiring on {$oneMonthFromNow}.");

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
                Mail::send('expiredContract', ['data' => $expiringContracts], function ($message) use ($recipients) {
                    $message->to($recipients);
                    $message->subject('Reminder for expired contract');
                });
                $trackingService->markMultipleSent($logIds);
                Log::info("Reminder email sent successfully to " . implode(', ', $recipients) . " for {$count} expiring contracts.");
            } catch (\Exception $e) {
                $trackingService->markMultipleFailed($logIds, $e->getMessage());
                Log::error("Failed to send contract expiry reminder: " . $e->getMessage());
            }
        } else {
            Log::info("No expiring contracts found to send reminders.");
        }

        return Command::SUCCESS;
    }
}
