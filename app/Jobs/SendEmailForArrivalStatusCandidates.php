<?php

namespace App\Jobs;

use App\Models\Arrival;
use App\Models\Candidate;
use App\Models\EmailLog;
use App\Models\Status;
use App\Services\EmailTrackingService;
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

    private const STATUS_BLADE_MAP = [
        Status::MIGRATION => 'StatusEmails.status_migration',
        Status::RECEIVED_PERMISSION => 'StatusEmails.status_received_permission',
        Status::SENT_DOCUMENTS_FOR_VISA => 'StatusEmails.status_sent_documents_for_visa',
        Status::SUBMITTED_AT_EMBASSY => 'StatusEmails.status_submitted_at_embassy',
        Status::RECEIVED_VISA => 'StatusEmails.status_received_visa',
        Status::ARRIVAL_EXPECTED => 'StatusEmails.status_waiting_to_arrive',
        Status::ARRIVED => 'StatusEmails.status_arrived',
        Status::PROCEDURE_FOR_ERPR => 'StatusEmails.status_procedure_for_ERPR',
        Status::LETTER_FOR_ERPR => 'StatusEmails.status_letter_for_ERPR',
        Status::PHOTO_FOR_ERPR => 'StatusEmails.status_photo_for_ERPR',
        Status::TAKING_ERPR => 'StatusEmails.status_taking_ERPR',
        Status::HIRED => 'StatusEmails.status_hired_for_job',
        Status::REFUSED_MIGRATION => 'StatusEmails.status_refused_migration',
        Status::REFUSED_CANDIDATE => 'StatusEmails.status_refused_candidate',
        Status::REFUSED_EMPLOYER => 'StatusEmails.status_refused_employer',
        Status::TERMINATED_CONTRACT => 'StatusEmails.status_terminated_contract',
        Status::FINISHED_CONTRACT => 'StatusEmails.status_finished_contract',
    ];

    public function __construct($statusId, $candidateId, $statusDate, $sendEmail)
    {
        $this->statusId = $statusId;
        $this->candidateId = $candidateId;
        $this->statusDate = $statusDate;
        $this->sendEmail = $sendEmail;
        $this->onQueue('mail');
    }

    public function handle(EmailTrackingService $trackingService)
    {
        $blade = self::STATUS_BLADE_MAP[$this->statusId] ?? null;

        if (!$blade) {
            Log::info("No email to send for status ID: {$this->statusId}");
            return;
        }

        $candidate = Candidate::with(['company', 'position'])->find($this->candidateId);

        if (!$candidate || !$candidate->company) {
            Log::error("Candidate or Company not found for ID: {$this->candidateId}");
            return;
        }

        $company = $candidate->company;
        $arrival = Arrival::where('candidate_id', $this->candidateId)->first();

        $data = [
            'candidateName' => $candidate->fullNameCyrillic,
            'candidateEmail' => $candidate->email ?? 'No Email Provided',
            'candidatePhone' => $candidate->phone ?? 'No Phone Provided',
            'companyName' => $company->nameOfCompany ?? 'Unknown Company',
            'jobPosition' => $candidate->position->jobPosition ?? 'Unknown Position',
            'contractType' => $candidate->contractType,
            'statusDate' => $this->statusDate,
            'companyAddress' => $company->address ?? 'No Address Provided',
            'arrivalDate' => $arrival->arrival_date ?? 'No Arrival Date Provided',
            'arrivalTime' => $arrival->arrival_time ?? 'No Arrival Time Provided',
        ];

        $nomadRecipients = array_filter(array_map('trim', explode(',', config('app.nomad_notification_emails', ''))));

        // 1. Send status email to company recipients (gated by sendEmail flag)
        if ($this->sendEmail) {
            $this->sendCompanyEmail($trackingService, $company, $blade, $data);
        }

        // 2. Send Nomad office emails (always — staff should know about every status change)
        if (empty($nomadRecipients)) {
            Log::warning("No NOMAD_NOTIFICATION_EMAILS configured. Skipping Nomad office notification.");
            return;
        }

        if ($this->statusId == Status::ARRIVAL_EXPECTED) {
            $this->sendNomadArrivalEmail($trackingService, $nomadRecipients, $data, $arrival);
        } else {
            $this->sendNomadStatusEmail($trackingService, $nomadRecipients, $data);
        }

        Log::info("Emails processed for candidate: {$data['candidateName']} (status ID: {$this->statusId})");
    }

    private function sendCompanyEmail(EmailTrackingService $trackingService, $company, string $blade, array $data): void
    {
        $recipients = $company->companyEmails()
            ->where('is_notification_recipient', true)
            ->pluck('email')
            ->toArray();

        if (empty($recipients) && !empty($company->default_email)) {
            $recipients = [$company->default_email];
        }

        if (empty($recipients)) {
            Log::info("No recipients found for company ID: {$company->id}");
            return;
        }

        $subject = 'Notification for ' . $data['candidateName'];

        $logIds = $trackingService->logMultipleEmails(
            recipients: $recipients,
            subject: $subject,
            emailType: EmailLog::TYPE_STATUS_NOTIFICATION,
            metadata: [
                'candidate_id' => $this->candidateId,
                'candidate_name' => $data['candidateName'],
                'company_name' => $data['companyName'],
                'status_id' => $this->statusId,
            ]
        );

        try {
            Mail::send($blade, ['data' => $data], function ($message) use ($subject, $recipients) {
                $message->to($recipients)->subject($subject);
            });
            $trackingService->markMultipleSent($logIds);
        } catch (\Exception $e) {
            $trackingService->markMultipleFailed($logIds, $e->getMessage());
            Log::error("Failed to send company email for candidate ID {$this->candidateId}: {$e->getMessage()}");
        }
    }

    private function sendNomadArrivalEmail(EmailTrackingService $trackingService, array $nomadRecipients, array $data, ?Arrival $arrival): void
    {
        $dataArrival = [
            'candidateName' => $data['candidateName'],
            'companyName' => $data['companyName'],
            'status' => 'Има билет за пристигане',
            'arrival_date' => $data['arrivalDate'],
            'arrival_time' => $data['arrivalTime'],
            'arrival_location' => $arrival->arrival_location ?? 'No Location Provided',
            'arrival_flight' => $arrival->arrival_flight ?? 'No Flight Info Provided',
            'where_to_stay' => $arrival->where_to_stay ?? 'No Accommodation Info Provided',
            'phone_number' => $data['candidatePhone'],
        ];

        $subject = 'Notification for Arrival ' . $data['candidateName'];

        $logIds = $trackingService->logMultipleEmails(
            recipients: $nomadRecipients,
            subject: $subject,
            emailType: EmailLog::TYPE_ARRIVAL_NOTIFICATION,
            metadata: [
                'candidate_id' => $this->candidateId,
                'candidate_name' => $data['candidateName'],
                'company_name' => $data['companyName'],
            ]
        );

        try {
            Mail::send('arrival', ['data' => $dataArrival], function ($message) use ($subject, $nomadRecipients) {
                $message->to($nomadRecipients)->subject($subject);
            });
            $trackingService->markMultipleSent($logIds);
        } catch (\Exception $e) {
            $trackingService->markMultipleFailed($logIds, $e->getMessage());
            Log::error("Failed to send Nomad arrival email for candidate ID {$this->candidateId}: {$e->getMessage()}");
        }
    }

    private function sendNomadStatusEmail(EmailTrackingService $trackingService, array $nomadRecipients, array $data): void
    {
        $statusName = Status::find($this->statusId)->nameOfStatus ?? 'Unknown Status';

        $dataForStatus = [
            'candidateName' => $data['candidateName'],
            'companyName' => $data['companyName'],
            'status' => $statusName,
            'changedStatusDate' => $this->statusDate,
            'phone_number' => $data['candidatePhone'],
            'description' => 'Status changed to ' . $statusName . ' on ' . $this->statusDate,
        ];

        $subject = 'Status Notification for ' . $data['candidateName'];

        $logIds = $trackingService->logMultipleEmails(
            recipients: $nomadRecipients,
            subject: $subject,
            emailType: EmailLog::TYPE_STATUS_NOTIFICATION,
            metadata: [
                'candidate_id' => $this->candidateId,
                'candidate_name' => $data['candidateName'],
                'company_name' => $data['companyName'],
                'status_name' => $statusName,
            ]
        );

        try {
            Mail::send('arrivalCandidateWithStatus', ['data' => $dataForStatus], function ($message) use ($subject, $nomadRecipients) {
                $message->to($nomadRecipients)->subject($subject);
            });
            $trackingService->markMultipleSent($logIds);
        } catch (\Exception $e) {
            $trackingService->markMultipleFailed($logIds, $e->getMessage());
            Log::error("Failed to send Nomad status email for candidate ID {$this->candidateId}: {$e->getMessage()}");
        }
    }
}
