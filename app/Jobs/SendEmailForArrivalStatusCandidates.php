<?php

namespace App\Jobs;

use App\Models\Arrival;
use App\Models\ArrivalCandidate;
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

class SendEmailForArrivalStatusCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $arrivalCandidateId;

    public function __construct($arrivalCandidateId)
    {
        $this->arrivalCandidateId = $arrivalCandidateId;
        $this->onQueue('mail');
    }

    public function handle()
    {
        // Log to check if the handle method is being executed
        Log::info("SendEmailForArrivalCandidates Job Started.");

        $arrivalCandidate = ArrivalCandidate::find($this->arrivalCandidateId);
        $arrival = Arrival::find($arrivalCandidate->arrival_id);
        $candidate = Candidate::find($arrival->candidate_id);
        $company = Company::find($arrival->company_id);

        $statusArrival = StatusArrival::find($arrivalCandidate->status_arrival_id);
        $status = $statusArrival->statusName;


        $data = [
            'candidateName' => $candidate->fullName,
            'companyName' => $company->nameOfCompany,
            'status' => $status,
            'changedStatusDate' => $arrivalCandidate->status_date,
            'description' => $arrivalCandidate->status_description,
            'phone_number' => $arrival->phone_number,
        ];



        try {
//            Mail::send('arrivalCandidateWithStatus', ['data' => $data], function ($message) use ($data) {
//                $message->to(['katya@nomadpartners.bg', 'sashko@nomadpartners.bg', 'georgi@nomadpartners.bg', 'milen@nomadpartners.bg'])
//                    ->subject('Notification for ' . $data['candidateName']);
//            });

            // Log success
            Log::info("Email sent successfully to " . $data['candidateName']);
        } catch (\Exception $e) {
            // Log any error if mail fails to send
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
