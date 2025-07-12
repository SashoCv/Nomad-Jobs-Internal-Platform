<?php

namespace App\Console\Commands;

use App\Models\Arrival;
use App\Models\Candidate;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddStatusHistoriesIdInArrivalTableCommand extends Command
{
    protected $signature = 'candidate:add-status-histories-id-in-arrival-table';
    protected $description = 'Add status histories ID in arrival table and create missing arrivals';

    public function handle()
    {
        $this->info('Започнува процесот...');

        $arrivals = Arrival::all();

        foreach ($arrivals as $arrival) {
            $statusHistory = Statushistory::where('id', $arrival->statushistories_id)->first();
            
            // Провери дали датумот е поминат од денешниот
            $arrivalDate = Carbon::parse($arrival->arrival_date);
            $today = Carbon::today();
            $statusId = $arrivalDate->isPast() ? 5 : 18; // 5 ако е поминат, 18 ако е иден или денешен

            if ($statusHistory) {
                $statusHistory->status_id = $statusId;
                $statusHistory->statusDate = $arrivalDate->format('Y-m-d');
                $statusHistory->save();

            } else {
                $newStatusHistory = new Statushistory();
                $newStatusHistory->candidate_id = $arrival->candidate_id;
                $newStatusHistory->status_id = $statusId;
                $newStatusHistory->statusDate = $arrivalDate->format('Y-m-d');
                $newStatusHistory->save();

                Log::info("New status history ID {$newStatusHistory->id} created with status {$statusId} and added to arrival ID {$arrival->id}");
            }
        }
    }
}
