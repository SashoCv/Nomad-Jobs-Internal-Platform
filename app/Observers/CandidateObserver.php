<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\Status;
use App\Models\Statushistory;

class CandidateObserver
{
    /**
     * Status IDs that should create calendar events
     */
    protected array $trackedStatuses = [
        Status::VISA_APPOINTMENT => [
            'type' => CalendarEvent::TYPE_VISA_APPOINTMENT,
            'title' => 'Насрочено за виза',
        ],
        Status::RECEIVED_VISA => [
            'type' => CalendarEvent::TYPE_RECEIVED_VISA,
            'title' => 'Получил виза',
        ],
        Status::PROCEDURE_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_PROCEDURE,
            'title' => 'Процедура за ЕРПР',
        ],
        Status::LETTER_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_LETTER,
            'title' => 'Писмо за ЕРПР',
        ],
        Status::PHOTO_FOR_ERPR => [
            'type' => CalendarEvent::TYPE_ERPR_PHOTO,
            'title' => 'Снимка за ЕРПР',
        ],
        Status::HIRED => [
            'type' => CalendarEvent::TYPE_HIRED,
            'title' => 'Назначен на работа',
        ],
    ];

    /**
     * Statuses that should exclude candidate from calendar
     */
    protected array $excludedStatuses = [
        Status::TERMINATED_CONTRACT,
        Status::REFUSED_MIGRATION,
        Status::REFUSED_CANDIDATE,
        Status::REFUSED_EMPLOYER,
        Status::REFUSED_BY_MIGRATION_OFFICE,
    ];

    /**
     * Handle the Candidate "updated" event.
     */
    public function updated(Candidate $candidate): void
    {
        // Check if status_id was changed
        if (!$candidate->isDirty('status_id')) {
            return;
        }

        $oldStatusId = $candidate->getOriginal('status_id');
        $newStatusId = $candidate->status_id;

        // If new status is excluded (terminated/refused), delete ALL calendar events for this candidate
        if (in_array($newStatusId, $this->excludedStatuses)) {
            CalendarEvent::where('candidate_id', $candidate->id)->delete();
            return;
        }

        // Delete old calendar event if old status was tracked
        if ($oldStatusId && isset($this->trackedStatuses[$oldStatusId])) {
            $oldMapping = $this->trackedStatuses[$oldStatusId];
            CalendarEvent::where('type', $oldMapping['type'])
                ->where('candidate_id', $candidate->id)
                ->delete();
        }

        // Create new calendar event if new status is tracked
        if ($newStatusId && isset($this->trackedStatuses[$newStatusId])) {
            $this->createCalendarEvent($candidate, $newStatusId);
        }
    }

    /**
     * Create calendar event for a candidate's status
     */
    protected function createCalendarEvent(Candidate $candidate, int $statusId): void
    {
        $mapping = $this->trackedStatuses[$statusId];

        // Get the latest status history entry for this status
        $statusHistory = Statushistory::where('candidate_id', $candidate->id)
            ->where('status_id', $statusId)
            ->whereNotNull('statusDate')
            ->orderBy('id', 'desc')
            ->first();

        if (!$statusHistory || !$statusHistory->statusDate) {
            return;
        }

        CalendarEvent::updateOrCreate(
            [
                'type' => $mapping['type'],
                'candidate_id' => $candidate->id,
            ],
            [
                'title' => $mapping['title'],
                'date' => $statusHistory->statusDate,
                'company_id' => $candidate->company_id,
                'description' => $statusHistory->description,
            ]
        );
    }
}
