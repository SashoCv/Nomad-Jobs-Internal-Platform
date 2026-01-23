<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'email_type',
        'status',
        'error_message',
        'metadata',
        'queued_at',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // Email type constants
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_WELCOME_SET_PASSWORD = 'welcome_set_password';
    const TYPE_DOCUMENT_SHARE = 'document_share';
    const TYPE_UNPAID_INVOICES = 'unpaid_invoices';
    const TYPE_ARRIVAL_NOTIFICATION = 'arrival_notification';
    const TYPE_ARRIVAL_COMPANY_NOTIFICATION = 'arrival_company_notification';
    const TYPE_STATUS_NOTIFICATION = 'status_notification';
    const TYPE_CONTRACT_EXPIRY_REMINDER = 'contract_expiry_reminder';
    const TYPE_VISA_EXPIRY_REMINDER = 'visa_expiry_reminder';

    // Status constants
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Mark the email as sent
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = now();
        $this->save();
    }

    /**
     * Mark the email as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->failed_at = now();
        $this->save();
    }
}
