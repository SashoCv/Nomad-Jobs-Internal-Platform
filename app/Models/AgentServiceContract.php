<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentServiceContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_service_contracts';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'agent_id',
        'agent_service_type_id',
        'contractNumber',
        'status',
        'startDate',
        'endDate',
    ];

    protected $casts = [
        'startDate' => 'date',
        'endDate' => 'date',
    ];

    /**
     * Boot method to auto-generate contract number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contractNumber)) {
                // Get current year
                $year = date('Y');

                // Generate contract number: AG-2025-{id}
                // We'll use a temporary placeholder and update after save
                $contract->contractNumber = 'TEMP-' . uniqid();
            }
        });

        static::created(function ($contract) {
            if (str_starts_with($contract->contractNumber, 'TEMP-')) {
                $year = date('Y');
                $contract->contractNumber = "AG-{$year}-{$contract->id}";
                $contract->saveQuietly(); // Use saveQuietly to avoid triggering events again
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function agentServiceType(): BelongsTo
    {
        return $this->belongsTo(AgentServiceType::class, 'agent_service_type_id');
    }

    public function contractPricing()
    {
        return $this->hasMany(AgentContractPricing::class, 'agent_service_contract_id');
    }

    /**
     * Scope to get only active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Set this contract as active and deactivate others for the same agent
     */
    public function setAsActive()
    {
        // First deactivate other active contracts for this agent
        self::where('agent_id', $this->agent_id)
            ->where('id', '!=', $this->id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_EXPIRED]);

        // Then set this contract as active
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get the active contract for an agent
     *
     * @param int $agentId
     * @return AgentServiceContract|null
     */
    public static function getActiveContract($agentId)
    {
        return self::where('agent_id', $agentId)
            ->active()
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Check if this is the active contract
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
