<?php

namespace App\Console\Commands;

use App\Models\Arrival;
use App\Models\Candidate;
use App\Models\Statushistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddStatusHistoriesIdInArrivalTableCommand extends Command
{
    protected $signature = 'candidate:add-status-histories-id-in-arrival-table';
    protected $description = 'Add status histories ID in arrival table and create missing arrivals';

    public function handle()
    {
        ini_set('memory_limit', '-1');
        $startTime = microtime(true);
        $this->info('Започнува процесот...');

        // Прв дел - поврзи постоечки arrivals со statushistory (како што веќе имаш)
        $statusHistories = Statushistory::where('status_id', 5)
            ->orWhere('status_id', 18)
            ->get()->keyBy('candidate_id');

        Arrival::chunk(500, function ($arrivals) use ($statusHistories) {
            foreach ($arrivals as $arrival) {
                $candidate = Candidate::find($arrival->candidate_id);

                if (!$candidate) {
                    Log::warning('Candidate with ID ' . $arrival->candidate_id . ' not found.');
                    continue;
                }

                $statusHistory = $statusHistories->get($candidate->id);

                if ($statusHistory) {
                    $arrival->statushistories_id = $statusHistory->id;
                    $arrival->save();

                    $statusHistory->statusDate = $arrival->arrival_date;
                    $statusHistory->save();

                    Log::info("Status history ID {$statusHistory->id} added to arrival ID {$arrival->id}");
                } else {
                    $newStatusHistory = new Statushistory();
                    $newStatusHistory->candidate_id = $candidate->id;
                    $newStatusHistory->status_id = 5;
                    $newStatusHistory->statusDate = $arrival->arrival_date;
                    $newStatusHistory->save();

                    $arrival->statushistories_id = $newStatusHistory->id;
                    $arrival->save();

                    Log::info("New status history ID {$newStatusHistory->id} created and added to arrival ID {$arrival->id}");
                }
            }
        });

        // Втор дел - креирај Arrival за кандидати со статус 5 или очаква се но без arrival
        $this->info('Креираме arrivals за кандидати без нив...');
        $candidatesWithStatus5 = Statushistory::where('status_id', 5)
            ->orWhere('status_id', 18)
            ->pluck('candidate_id')->unique();

        foreach ($candidatesWithStatus5 as $candidateId) {
            $candidate = Candidate::find($candidateId);

            if (!$candidate) {
                continue;
            }

            $hasArrival = Arrival::where('candidate_id', $candidateId)->exists();
            if (!$hasArrival) {
                $statusHistory = Statushistory::where('candidate_id', $candidateId)
                    ->latest('statusDate')
                    ->first();

                if (!$statusHistory) {
                    continue;
                }

                $arrival = new Arrival();
                $arrival->candidate_id = $candidateId;
                $arrival->company_id = $candidate->company_id;
                $arrival->arrival_date = '2020-01-01';
                $arrival->arrival_time = '00:00:00';
                $arrival->arrival_location = 'Стар кандидат (генерирано)';
                $arrival->arrival_flight = 'Стар кандидат (генерирано)';
                $arrival->where_to_stay = 'Стар кандидат (генерирано)';
                $arrival->phone_number = $candidate->phoneNumber;
                $arrival->statushistories_id = $statusHistory->id;
                $arrival->save();

                Log::info("New arrival created for candidate ID {$candidateId} (auto-generated).");
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✅ Готово за $duration секунди.");
    }
}
