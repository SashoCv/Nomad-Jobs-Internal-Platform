<?php

namespace App\Console\Commands;

use App\Models\ArrivalCandidate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChangeCandidateStatusToArrival extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidate:change-status-to-arrival';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the status of a candidate to arrival';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentTime = Carbon::now('Europe/Sofia')->format('H:i');
        $candidateWithStatusNeedToArrive = ArrivalCandidate::with('arrival')->where('status_arrival_id', '=',8)->get();
        foreach ($candidateWithStatusNeedToArrive as $candidate) {
            if($candidate->arrival->arrival_date == date('Y-m-d') && ($candidate->arrival->arrival_time == $currentTime || $candidate->arrival->arrival_time < $currentTime)){
                $candidate->status_arrival_id = 1;
                $candidate->status_date = $candidate->arrival->arrival_date;
                $candidate->status_description = 'Candidate arrived';
                $candidate->save();
            }
        }
        $this->info('Changing the status of the candidate to arrival');
        $this->info('Candidate status changed successfully' . $currentTime);
    }
}
