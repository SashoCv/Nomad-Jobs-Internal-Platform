<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChangeAllCountriesWithBulgarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:all-countries-with-bulgarian';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change all countries with Bulgarian names';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $allCandidates = \App\Models\Candidate::all();

        foreach ($allCandidates as $candidate) {
            if (strpos($candidate->country, 'ја')) {
                $candidate->country = str_replace('ја', 'я', $candidate->country);
                $candidate->save();
                $this->info("Updated country for candidate ID {$candidate->id} to {$candidate->country}");
            } else {
                $this->info("No change needed for candidate ID {$candidate->id}");
            }
        }
    }
}
