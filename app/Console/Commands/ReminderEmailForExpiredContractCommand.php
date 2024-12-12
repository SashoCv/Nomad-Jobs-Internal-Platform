<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
        $expiredContractsOneMonthAgo = Carbon::now()->subMonth()->format('Y-m-d');

        $allCandidatesWithThisDate = Candidate::with('company')
            ->where('contractPeriodDate', $expiredContractsOneMonthAgo)->get();

        if ($allCandidatesWithThisDate->count() > 0) {
            Mail::send('expiredContract', ['data' => $allCandidatesWithThisDate], function ($message) use ($allCandidatesWithThisDate) {
                $message->to(['sasocvetanoski@gmail.com']);
                $message->subject('Reminder for expired contract');
            });
        }
    }
}
