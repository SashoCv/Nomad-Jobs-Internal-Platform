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

class SendEmailToCompany implements ShouldQueue
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
        Log::info("SendEmailForArrivalCandidates Job Started.");

        $arrivalCandidate = ArrivalCandidate::find($this->arrivalCandidateId);
        $arrival = Arrival::find($arrivalCandidate->arrival_id);
        $candidate = Candidate::find($arrival->candidate_id);
        $company = Company::find($arrival->company_id);
        $email = $company->email;
        $statusArrival = StatusArrival::find($arrivalCandidate->status_arrival_id);
        $status = $statusArrival->statusName;


        $data = [
            'candidateName' => $candidate->fullName,
            'companyName' => $company->nameOfCompany,
            'status' => $status,
            'contractType' => $candidate->contractType,
            'changedStatusDate' => $arrivalCandidate->status_date,
            'description' => $arrivalCandidate->status_description,
            'phone_number' => $arrival->phone_number,
            'arrivalTime' => $arrival->arrival_time,
            'arrivalDate' => $arrival->arrival_date,
        ];



        try {
            Mail::send('arrivalCandidateForCompany', ['data' => $data], function ($message) use ($data, $email) {
                $message->to($email)
                    ->subject('Уведомление за пристигане на ' . $data['candidateName']);
            });

            Log::info("Email sent successfully to " . $data['candidateName']);
        } catch (\Exception $e) {
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
