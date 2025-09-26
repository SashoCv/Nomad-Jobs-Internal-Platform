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
    protected $sendEmail;

    public function __construct($statusId, $candidateId, $statusDate, $sendEmail)
    {
        $this->statusId = $statusId;
        $this->candidateId = $candidateId;
        $this->statusDate = $statusDate;
        $this->sendEmail = $sendEmail;
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

            if($this->sendEmail){
                Mail::send($blade, ['data' => $data], function ($message) use ($candidate, $data, $company) {
                    $message->to($company->companyEmail)
                        ->subject('Notification for ' . $data['candidateName']);
                });
            }


            if($this->statusId == 18){
                $dataArrival = [
                    'candidateName' => $data['candidateName'],
                    'companyName' => $data['companyName'],
                    'status' => 'Има билет за пристигане',
                    'arrival_date' => $data['arrivalDate'],
                    'arrival_time' => $data['arrivalTime'],
                    'arrival_location' => Arrival::where('candidate_id', $this->candidateId)->value('arrival_location') ?? 'No Location Provided',
                    'arrival_flight' => Arrival::where('candidate_id', $this->candidateId)->value('arrival_flight') ?? 'No Flight Info Provided',
                    'where_to_stay' => Arrival::where('candidate_id', $this->candidateId)->value('where_to_stay') ?? 'No Accommodation Info Provided',
                    'phone_number' => $data['candidatePhone'],
                ];

                Mail::send("arrival", ['data' => $dataArrival], function($message) use ($data) {
                    $message->to(['gabriela@nomadpartners.bg', 'katya@nomadpartners.bg', 'sashko@nomadpartners.bg', 'georgi@nomadpartners.bg', 'milen@nomadpartners.bg'])
                        ->subject('Notification for Arrival ' . $data['candidateName']);
                });

            }

            // For all other statuses that are not 18 or 5, send email to nomadOffice
            if($this->statusId != 18) {
                $statusName = Candidate::with('latestStatusHistory.status')->find($this->candidateId)->latestStatusHistory->status->nameOfStatus ?? 'Unknown Status';
                $dataForAllStatuses = [
                    'candidateName' => $data['candidateName'],
                    'companyName' => $data['companyName'],
                    'status' => $statusName,
                    'changedStatusDate' => $this->statusDate,
                    'phone_number' => $data['candidatePhone'],
                    'description' => 'Status changed to ' . $statusName . ' on ' . $this->statusDate,
                ];

                Mail::send("arrivalCandidateWithStatus", ['data' => $dataForAllStatuses], function($message) use ($data) {
                    $message->to(['gabriela@nomadpartners.bg', 'katya@nomadpartners.bg', 'sashko@nomadpartners.bg', 'georgi@nomadpartners.bg', 'milen@nomadpartners.bg'])
                        ->subject('Notification for Arrival ' . $data['candidateName']);
                });
            }

            // Log success
            Log::info("Email sent successfully to " . $data['candidateName']);
        } catch (\Exception $e) {
            Log::info("Failed to send email for candidate ID: " . $this->candidateId);
            Log::error("Error sending email: " . $e->getMessage());
        }
    }
}
