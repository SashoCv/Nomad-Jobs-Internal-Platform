<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddStatusHistoryForCandidatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidate:add-status-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add status history for candidates';

    public function handle()
    {
        $candidates = Candidate::all();

        foreach ($candidates as $candidate){
            if($candidate->status_id == null){
                Log::warning('Candidate with ID ' . $candidate->id . ' has no status_id.');
                continue;
            }

            $statusHistory = new Statushistory();
            $statusHistory->candidate_id = $candidate->id;
            $statusHistory->status_id = $candidate->status_id;
            $statusHistory->statusDate = Carbon::parse($candidate->updated_at)->format('Y-m-d') ?? Carbon::parse($candidate->created_at)->format('Y-m-d');

            $statusHistory->save();
        }

        Log::info('Status history added for all candidates.');

        $this->info('Status history added for all candidates.');
    }
}
