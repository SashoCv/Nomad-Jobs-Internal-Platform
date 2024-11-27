<?php

namespace App\Console\Commands;

use App\Models\ArrivalCandidate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeDateTypeInStatussCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:date-type-in-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change date type in statuses table from date to datetime';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dates = ArrivalCandidate::where('status_arrival_id', '!=', 7)->get();

        foreach ($dates as $date) {
            if($parsedDate = Carbon::createFromFormat('m-d-Y', $date->status_date)){
                $date->status_date = $parsedDate->format('d-m-Y');

                $date->save();
            }
        }
    }
}
