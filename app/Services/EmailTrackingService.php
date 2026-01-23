<?php

namespace App\Services;

use App\Models\EmailLog;

class EmailTrackingService
{
    /**
     * Log an email as queued
     *
     * @param string $recipientEmail
     * @param string $subject
     * @param string $emailType
     * @param string|null $recipientName
     * @param array $metadata
     * @return int The email log ID
     */
    public function logEmail(
        string $recipientEmail,
        string $subject,
        string $emailType,
        ?string $recipientName = null,
        array $metadata = []
    ): int {
        $log = EmailLog::create([
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'email_type' => $emailType,
            'status' => EmailLog::STATUS_QUEUED,
            'metadata' => !empty($metadata) ? $metadata : null,
            'queued_at' => now(),
        ]);

        return $log->id;
    }

    /**
     * Mark an email as sent
     *
     * @param int $logId
     * @return void
     */
    public function markSent(int $logId): void
    {
        $log = EmailLog::find($logId);

        if ($log) {
            $log->markAsSent();
        }
    }

    /**
     * Mark an email as failed
     *
     * @param int $logId
     * @param string $errorMessage
     * @return void
     */
    public function markFailed(int $logId, string $errorMessage): void
    {
        $log = EmailLog::find($logId);

        if ($log) {
            $log->markAsFailed($errorMessage);
        }
    }

    /**
     * Log multiple emails (for bulk sending)
     *
     * @param array $recipients Array of ['email' => string, 'name' => string|null]
     * @param string $subject
     * @param string $emailType
     * @param array $metadata
     * @return array Array of log IDs
     */
    public function logMultipleEmails(
        array $recipients,
        string $subject,
        string $emailType,
        array $metadata = []
    ): array {
        $logIds = [];

        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? null) : null;

            $logIds[] = $this->logEmail($email, $subject, $emailType, $name, $metadata);
        }

        return $logIds;
    }

    /**
     * Mark multiple emails as sent
     *
     * @param array $logIds
     * @return void
     */
    public function markMultipleSent(array $logIds): void
    {
        foreach ($logIds as $logId) {
            $this->markSent($logId);
        }
    }

    /**
     * Mark multiple emails as failed
     *
     * @param array $logIds
     * @param string $errorMessage
     * @return void
     */
    public function markMultipleFailed(array $logIds, string $errorMessage): void
    {
        foreach ($logIds as $logId) {
            $this->markFailed($logId, $errorMessage);
        }
    }
}
