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
use Illuminate\Support\Facades\Log;

class SendEmailForArrivalCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $arrival;
    protected $statusId;

    public function __construct($arrival, $statusId)
    {
        $this->arrival = $arrival;
        $this->statusId = $statusId;
        $this->onQueue('mail');
    }

    public function handle()
    {
        // Log to check if the handle method is being executed
        Log::info("SendEmailForArrivalCandidates Job Started.");

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

        try {
            Mail::send('arrival', ['data' => $data], function ($message) use ($data) {
                $emails = explode(',', env('ARRIVAL_NOTIFICATION_EMAILS'));
                $message->to($emails)
                    ->subject('Arrival Notification for ' . $data['candidateName']);
            });


            // Log success
            Log::info("Email sent successfully to " . $data['candidateName']);
        } catch (\Exception $e) {
            // Log any error if mail fails to send
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
