<?php

namespace App\Console\Commands;

use App\Models\CandidateVisa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReminderEmailForExpiringVisaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:email-for-expiring-visa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder email for expiring visas (60 days and 30 days before)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now();
        
        // Get visas expiring in 60 days
        $sixtyDaysFromNow = $today->copy()->addDays(60)->format('Y-m-d');
        $visasExpiring60Days = CandidateVisa::with(['candidate.company'])
            ->whereDate('end_date', $sixtyDaysFromNow)
            ->get();

        // Get visas expiring in 30 days
        $thirtyDaysFromNow = $today->copy()->addDays(30)->format('Y-m-d');
        $visasExpiring30Days = CandidateVisa::with(['candidate.company'])
            ->whereDate('end_date', $thirtyDaysFromNow)
            ->get();

        $count60 = $visasExpiring60Days->count();
        $count30 = $visasExpiring30Days->count();

        Log::info("Found {$count60} visas expiring in 60 days and {$count30} visas expiring in 30 days.");

        if ($count60 > 0) {
            $this->sendEmail($visasExpiring60Days, '60 дни');
            Log::info("Sent reminder email for {$count60} visas expiring in 60 days.");
        }

        if ($count30 > 0) {
            $this->sendEmail($visasExpiring30Days, '30 дни');
            Log::info("Sent reminder email for {$count30} visas expiring in 30 days.");
        }

        if ($count60 === 0 && $count30 === 0) {
            Log::info("No visas expiring soon. No emails sent.");
        }

        $this->info("Visa expiry reminder check completed. 60-day: {$count60}, 30-day: {$count30}");

        return Command::SUCCESS;
    }

    /**
     * Send the reminder email.
     */
    private function sendEmail($visas, string $daysRemaining): void
    {
        $recipients = array_filter(array_map('trim', explode(',', env('NOMAD_NOTIFICATION_EMAILS', ''))));

        if (empty($recipients)) {
            Log::warning("No recipients configured in NOMAD_NOTIFICATION_EMAILS. Skipping email.");
            return;
        }

        $data = [
            'visas' => $visas,
            'daysRemaining' => $daysRemaining,
        ];

        Mail::send('expiringVisa', ['data' => $data], function ($message) use ($daysRemaining, $recipients) {
            $message->to($recipients);
            $message->subject("Напомняне: Визи изтичащи след {$daysRemaining}");
        });

        Log::info("Visa expiry reminder sent to: " . implode(', ', $recipients));
    }
}
