<?php

namespace App\Jobs;

use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Position;
use App\Models\StatusArrival;
use Carbon\Carbon;
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

    public function __construct(int $arrivalCandidateId)
    {
        $this->arrivalCandidateId = $arrivalCandidateId;
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        Log::info("SendEmailForArrivalCandidates Job Started.");

        $arrivalCandidate = ArrivalCandidate::find($this->arrivalCandidateId);

        if (! $arrivalCandidate) {
            Log::error("ArrivalCandidate not found: {$this->arrivalCandidateId}");
            return;
        }

        $arrival = Arrival::find($arrivalCandidate->arrival_id);

        if (! $arrival) {
            Log::error("Arrival not found: {$arrivalCandidate->arrival_id}");
            return;
        }

        $candidate = Candidate::find($arrival->candidate_id);
        $company = Company::find($arrival->company_id);

        if (! $candidate || ! $company) {
            Log::error("Candidate or Company not found for arrival: {$arrival->id}");
            return;
        }

        $position = Position::find($candidate->position_id);
        $statusArrival = StatusArrival::find($arrivalCandidate->status_arrival_id);

        $data = [
            'candidateName' => $candidate->fullNameCyrillic,
            'companyName' => $company->nameOfCompany,
            'status' => $statusArrival?->statusName,
            'jobPosition' => $position?->jobPosition,
            'contractType' => $this->getTypeOfContract($candidate->contractType),
            'changedStatusDate' => $arrivalCandidate->status_date,
            'description' => $arrivalCandidate->status_description,
            'phone_number' => $arrival->phone_number,
            'arrivalTime' => $arrival->arrival_time,
            'arrivalDate' => Carbon::parse($arrival->arrival_date)->format('d.m.Y'),
            'companyAddress' => $company->address,
            'personPicture' => $candidate->personPicturePath,
        ];

        try {
//            Mail::send('arrivalCandidateForCompany', ['data' => $data], function ($message) use ($data, $email) {
//                $message->to($email)
//                    ->subject('Уведомление за пристигане на ' . $data['candidateName']);
//            });

            Log::info("Email sent successfully to " . $company->default_email);
        } catch (\Exception $e) {
            Log::error("Error sending email: " . $e->getMessage());
        }
    }

    public function getTypeOfContract(string $contractType): string
    {
        return match ($contractType) {
            'erpr1', 'erpr2', 'erpr3' => 'ЕРПР',
            '90days' => '90 дена',
            '9months' => '9 месеца',
            default => 'Непознат тип на договор',
        };
    }
}
