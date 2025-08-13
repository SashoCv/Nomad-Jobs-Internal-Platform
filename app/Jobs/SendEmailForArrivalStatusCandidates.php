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

    protected $statusId;
    protected $candidateId;
    protected $statusDate;

    public function __construct($statusId, $candidateId, $statusDate)
    {
        $this->statusId = $statusId;
        $this->candidateId = $candidateId;
        $this->statusDate = $statusDate;
        $this->onQueue('mail');
    }

    public function handle()
    {
        // Log to check if the handle method is being executed
        Log::info("SendEmailForArrivalCandidates Job Started.");

        // here i need base on the status to send different mail
        switch ($this->statusId) {
            case 1: // migration
                $blade = 'StatusEmails.status_migration';
                break;
            case 2: // Получил разрешение.
                $blade = 'StatusEmails.status_received_permission';
                break;
            case 15: // Изпратени документи за виза
                $blade = 'StatusEmails.status_sent_documents_for_visa';
                break;
            case 3: // Подаден в посолството.
                $blade = 'StatusEmails.status_submitted_at_embassy';
                break;
            case 4: // Получил виза.
                $blade = 'StatusEmails.status_received_visa';
                break;
            case 18: // Очаква се.
                $blade = 'StatusEmails.status_waiting_to_arrive';
                break;
            case 5: // Пристигнал.
                $blade = 'StatusEmails.status_arrived';
                break;
            case 6: // Процедура за ЕРПР.
                $blade = 'StatusEmails.status_procedure_for_ERPR';
                break;
            case 17: // Писмо за ЕРПР.
                $blade = 'StatusEmails.status_letter_for_ERPR';
                break;
            case 7: // Снимка за ЕРПР.
                $blade = 'StatusEmails.status_photo_for_ERPR';
                break;
            case 8: // Получаване на ЕРПР.
                $blade = 'StatusEmails.status_taking_ERPR';
                break;
            case 9: // Назначен на работа.
                $blade = 'StatusEmails.status_hired_for_job';
                break;
            case 12: // Отказ от Миграция.
                $blade = 'StatusEmails.status_refused_migration';
                break;
            case 13: // Отказ от кандидата.
                $blade = 'StatusEmails.status_refused_candidate';
                break;
            case 14: // Отказ от работодателя.
                $blade = 'StatusEmails.status_refused_employer';
                break;
            case 11: // Прекратен договор.
                $blade = 'StatusEmails.status_terminated_contract';
                break;
            case 10: // Приключил договор.
                $blade = 'StatusEmails.status_finished_contract';
                break;
            default:
                Log::info("No email to send for status ID: " . $this->statusId);
                return;
        }

        $candidate = Candidate::find($this->candidateId);
        $company = $candidate->company;

        if (!$candidate || !$company) {
            Log::error("Candidate or Company not found for ID: " . $this->candidateId);
            return;
        }


        Log::info('contractType: ' . $candidate->contractType);
        $data = [
            'candidateName' => $candidate->fullNameCyrillic,
            'candidateEmail' => $candidate->email ?? 'No Email Provided',
            'candidatePhone' => $candidate->phone ?? 'No Phone Provided',
            'companyName' => $company->nameOfCompany ?? 'Unknown Company',
            'jobPosition' => $candidate->position->jobPosition ?? 'Unknown Position',
            'contractType' => $candidate->contractType,
            'statusDate' => $this->statusDate,
            'companyAddress' => $company->address ?? 'No Address Provided',
            'arrivalDate' => Arrival::where('candidate_id', $this->candidateId)->value('arrival_date') ?? 'No Arrival Date Provided',
            'arrivalTime' => Arrival::where('candidate_id', $this->candidateId)->value('arrival_time') ?? 'No Arrival Time Provided',
        ];

        try {
            if($company->companyEmail == null) {
                Log::info("Company email is null for candidate ID: " . $this->candidateId);
                return;
            }
            Mail::send($blade, ['data' => $data], function ($message) use ($candidate, $data, $company) {
                $message->to("sasocvetanoski@gmail.com")
                ->subject('Notification for ' . $data['candidateName']);
            });

            // Log success
            Log::info("Email sent successfully to " . $data['candidateName']);
        } catch (\Exception $e) {
            Log::info("Failed to send email for candidate ID: " . $this->candidateId);
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
