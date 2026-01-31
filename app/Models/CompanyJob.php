<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyJob extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FILLED = 'filled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_FILLED,
        self::STATUS_REJECTED,
        self::STATUS_REVISION_REQUESTED,
    ];

    // Editable fields that can be included in a revision
    public const REVISION_FIELDS = [
        'job_title',
        'job_description',
        'number_of_positions',
        'contract_type',
        'requirementsForCandidates',
        'salary',
        'bonus',
        'workTime',
        'additionalWork',
        'vacationDays',
        'rent',
        'food',
        'otherDescription',
    ];

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'pending_revision' => 'array',
        'revision_requested_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changeLogs()
    {
        return $this->hasMany(ChangeLog::class, 'record_id')->where('tableName', 'company_jobs');
    }

    public function companyRequest()
    {
        return $this->hasOne(CompanyRequest::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function agentCandidates()
    {
        return $this->hasMany(AgentCandidate::class, 'company_job_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisionRequestedBy()
    {
        return $this->belongsTo(User::class, 'revision_requested_by');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isFilled(): bool
    {
        return $this->status === self::STATUS_FILLED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeActivated(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_INACTIVE, self::STATUS_FILLED]);
    }

    public function canBeDeactivated(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the job posting should be marked as filled
     * Called after candidate approval
     */
    public function checkAndUpdateFilledStatus(): void
    {
        $approvedCandidatesCount = $this->agentCandidates()
            ->whereHas('statuses', function ($query) {
                $query->where('statusForCandidateFromAgents.status', 'approved');
            })
            ->count();

        if ($approvedCandidatesCount >= $this->number_of_positions && $this->status === self::STATUS_ACTIVE) {
            $this->update(['status' => self::STATUS_FILLED]);
        }
    }

    // Revision methods

    public function isRevisionRequested(): bool
    {
        return $this->status === self::STATUS_REVISION_REQUESTED;
    }

    public function hasPendingRevision(): bool
    {
        return !empty($this->pending_revision);
    }

    /**
     * Check if the job posting can have a revision requested
     * Only active or filled jobs can have revisions
     */
    public function canRequestRevision(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_FILLED]);
    }

    /**
     * Submit a revision request
     */
    public function submitRevision(array $proposedData, int $userId): void
    {
        // Filter to only include valid revision fields
        $revision = array_intersect_key($proposedData, array_flip(self::REVISION_FIELDS));

        // Store the previous status so we can restore it on rejection
        $revision['_previous_status'] = $this->status;

        $this->update([
            'pending_revision' => $revision,
            'revision_requested_by' => $userId,
            'revision_requested_at' => now(),
            'status' => self::STATUS_REVISION_REQUESTED,
        ]);
    }

    /**
     * Approve the pending revision and apply changes
     */
    public function approveRevision(int $reviewerId): void
    {
        if (!$this->hasPendingRevision()) {
            throw new \Exception('No pending revision to approve');
        }

        $revision = $this->pending_revision;
        $previousStatus = $revision['_previous_status'] ?? self::STATUS_ACTIVE;
        unset($revision['_previous_status']);

        // Apply all revision fields
        $this->update(array_merge($revision, [
            'pending_revision' => null,
            'revision_requested_by' => null,
            'revision_requested_at' => null,
            'status' => $previousStatus,
        ]));
    }

    /**
     * Reject the pending revision
     */
    public function rejectRevision(): void
    {
        if (!$this->hasPendingRevision()) {
            throw new \Exception('No pending revision to reject');
        }

        $previousStatus = $this->pending_revision['_previous_status'] ?? self::STATUS_ACTIVE;

        $this->update([
            'pending_revision' => null,
            'revision_requested_by' => null,
            'revision_requested_at' => null,
            'status' => $previousStatus,
        ]);
    }

    /**
     * Get the differences between current values and pending revision
     */
    public function getRevisionDiff(): array
    {
        if (!$this->hasPendingRevision()) {
            return [];
        }

        $diff = [];
        $revision = $this->pending_revision;
        unset($revision['_previous_status']);

        foreach ($revision as $field => $newValue) {
            $currentValue = $this->getOriginal($field) ?? $this->$field;
            if ($currentValue != $newValue) {
                $diff[$field] = [
                    'current' => $currentValue,
                    'proposed' => $newValue,
                ];
            }
        }

        return $diff;
    }
}
