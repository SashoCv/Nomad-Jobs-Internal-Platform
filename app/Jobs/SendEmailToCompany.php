<?php

namespace App\Jobs;

use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Position;
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
        $positionId = $candidate->position_id;
        $position = Position::find($positionId);
        $typeOfContract = $this->getTypeOfContract($candidate->contractType);
        $companyAddress = $company->address;
        $email = $company->email;
        $statusArrival = StatusArrival::find($arrivalCandidate->status_arrival_id);
        $status = $statusArrival->statusName;
        $email = "sasocvetanoski@gmail.com";


        $data = [
            'candidateName' => $candidate->fullNameCyrillic,
            'companyName' => $company->nameOfCompany,
            'status' => $status,
            'jobPosition' => $position->jobPosition,
            'contractType' => $typeOfContract,
            'changedStatusDate' => $arrivalCandidate->status_date,
            'description' => $arrivalCandidate->status_description,
            'phone_number' => $arrival->phone_number,
            'arrivalTime' => $arrival->arrival_time,
            'arrivalDate' => $arrival->arrival_date,
            'companyAddress' => $companyAddress,
        ];



        try {
            Mail::send('arrivalCandidateForCompany', ['data' => $data], function ($message) use ($data, $email) {
                $message->to($email)
                    ->subject('Уведомление за пристигане на ' . $data['candidateName']);
            });

            Log::info("Email sent successfully to " . $email);
        } catch (\Exception $e) {
            Log::error("Error sending email: " . $e->getMessage());
        }
    }

    public function getTypeOfContract($contractType)
    {
        if($contractType == "indefinite") {
            return "ЕРПР";
        } elseif($contractType == "90days") {
            return "90 дена";
        } elseif($contractType == "9months") {
            return "9 месеци";
        } else {
            return "Непознат тип на договор";
        }
    }
}
