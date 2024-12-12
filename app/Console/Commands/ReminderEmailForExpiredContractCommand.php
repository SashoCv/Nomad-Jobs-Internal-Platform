<?php

namespace App\Console\Commands;

use App\Models\Candidate;
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
    public function handle()
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
            Mail::send('expiredContract', ['data' => $allCandidatesWithThisDate], function ($message) {
                $message->to(['sasocvetanoski@gmail.com']);
                $message->subject('Reminder for expired contract');
            });

            // Log the success of the email sending process
            Log::info("Reminder email sent successfully to sasocvetanoski@gmail.com for {$count} expired contracts.");
        } else {
            // Log no candidates found
            Log::info("No expired contracts found to send reminders.");
        }

        return Command::SUCCESS;
    }
}

