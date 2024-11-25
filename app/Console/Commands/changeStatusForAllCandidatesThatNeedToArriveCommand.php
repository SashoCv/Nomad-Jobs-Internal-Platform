<?php

namespace App\Console\Commands;

use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Console\Command;

class changeStatusForAllCandidatesThatNeedToArriveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change-status:arrive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change status for all candidates that need to arrive, with progress bar and statistics';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $allCandidatesWithStatusVisa = Candidate::where('status_id', '4')->get();
        $totalCandidates = $allCandidatesWithStatusVisa->count();

        if ($totalCandidates === 0) {
            $this->info('No candidates found with the status "Polucil viza".');
            return 0;
        }

        $this->info("Found {$totalCandidates} candidates with status 'Pulucil Viza'. Processing...");

        $this->output->progressStart($totalCandidates);

        $updatedCount = 0;

        foreach ($allCandidatesWithStatusVisa as $candidate) {
            $arrival = new Arrival();
            $arrival->company_id = $candidate->company_id;
            $arrival->candidate_id = $candidate->id;
            $arrival->arrival_date = null;
            $arrival->arrival_time = null;
            $arrival->arrival_location = null;
            $arrival->arrival_flight = null;
            $arrival->where_to_stay = null;
            $arrival->phone_number = null;

            if ($arrival->save()) {
                $arrivalCandidate = new ArrivalCandidate();
                $arrivalCandidate->arrival_id = $arrival->id;
                $arrivalCandidate->status_arrival_id = 7;
                $arrivalCandidate->status_description = 'Получил виза';
                $arrivalCandidate->status_date = Carbon::now()->format('d-m-Y');
                $arrivalCandidate->save();

                $category = new Category();
                $category->nameOfCategory = 'Documents For Arrival Candidates';
                $category->candidate_id = $arrival->candidate_id;
                $category->role_id = 2;
                $category->isGenerated = 0;
                $category->save();

                $updatedCount++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Processing completed. {$updatedCount} candidates were updated out of {$totalCandidates}.");

        return 0;
    }
}
