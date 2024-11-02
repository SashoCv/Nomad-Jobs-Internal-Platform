<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\StatusArrival;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailForArrivalCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'mail';
    protected $arrival;
    protected $statusId;

    public function __construct($arrival, $statusId)
    {
        $this->arrival = $arrival;
        $this->statusId = $statusId;
    }

    public function handle()
    {
        $candidate = Candidate::find($this->arrival->candidate_id);
        $company = Company::find($this->arrival->company_id);
        $status = StatusArrival::find($this->statusId)->statusName;

        $data = [
            'candidateName' => $candidate->fullName,
            'companyName' => $company->nameOfCompany,
            'status' => $status,
            'arrival_date' => $this->arrival->arrival_date,
            'arrival_time' => $this->arrival->arrival_time,
            'arrival_location' => $this->arrival->arrival_location,
            'arrival_flight' => $this->arrival->arrival_flight,
            'where_to_stay' => $this->arrival->where_to_stay,
            'phone_number' => $this->arrival->phone_number,
        ];

        Mail::send('emails.arrival', ['data' => $data], function ($message) use ($data) {
            $message->to('sasocvetanoski@gmail.com')->subject('Arrival Notification for ' . $data['candidateName']);
        });
    }
}
