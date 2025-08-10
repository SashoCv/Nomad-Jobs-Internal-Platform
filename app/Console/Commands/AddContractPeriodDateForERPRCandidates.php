<?php

namespace App\Console\Commands;

use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddContractPeriodDateForERPRCandidates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidate:add-contract-period-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add contract period date for ERP candidates';


    public function handle()
    {
        $allCandidates = Candidate::all();

        foreach ($allCandidates as $candidate) {
           if($candidate->contractType === "indefinite" || str_starts_with($candidate->contractType, 'ERPR')){
               $date = Carbon::parse($candidate->date);
               $contractPeriodRaw = $candidate->contractPeriod;

               preg_match('/\d+/', $contractPeriodRaw, $matches);
               $contractPeriod = isset($matches[0]) ? (int) $matches[0] : null;

               if($contractPeriod === null){
                   $contractPeriodDate = null;
               } else {
                   $contractPeriodDate = $date->addYears($contractPeriod);
               }

               $candidate->contractPeriodDate = $contractPeriodDate;
               $candidate->save();
           }
        }
    }
}
