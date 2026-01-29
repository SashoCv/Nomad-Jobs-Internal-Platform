<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CandidateContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidate_contracts';

    protected $fillable = [
        'candidate_id',
        'company_id',
        'position_id',
        'status_id',
        'type_id',
        'contract_type',
        'contract_period',
        'contract_period_number',
        'contract_extension_period',
        'start_contract_date',
        'end_contract_date',
        'contract_period_date',
        'salary',
        'working_time',
        'working_days',
        'address_of_work',
        'name_of_facility',
        'company_adresses_id',
        'dossier_number',
        'quartal',
        'seasonal',
        'case_id',
        'agent_id',
        'user_id',
        'added_by',
        'date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_contract_date' => 'date',
        'end_contract_date' => 'date',
        'contract_period_date' => 'date',
        'date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================
    // Relationships
    // ==================

    /**
     * Get the candidate (person) this contract belongs to
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the company for this contract
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the position for this contract
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the status for this contract
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the type for this contract
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Get the company address for this contract
     */
    public function companyAddress(): BelongsTo
    {
        return $this->belongsTo(CompanyAdress::class, 'company_adresses_id');
    }

    /**
     * Get the agent for this contract
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the authorized user for this contract
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the case for this contract
     */
    public function cases(): BelongsTo
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    /**
     * Get files associated with this contract
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'contract_id');
    }

    /**
     * Get status histories for this contract
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(Statushistory::class, 'contract_id');
    }

    /**
     * Get agent candidates for this contract
     */
    public function agentCandidates(): HasMany
    {
        return $this->hasMany(AgentCandidate::class, 'contract_id');
    }

    /**
     * Get invoices for this contract
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'contract_id');
    }

    /**
     * Get arrivals for this contract
     */
    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class, 'contract_id');
    }

    /**
     * Get visas for this contract
     */
    public function visas(): HasMany
    {
        return $this->hasMany(CandidateVisa::class, 'contract_id');
    }

    // ==================
    // Scopes
    // ==================

    /**
     * Filter by active contracts only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter contracts expiring within given months
     */
    public function scopeExpiringSoon(Builder $query, int $months = 4): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('end_contract_date')
            ->whereDate('end_contract_date', '<=', Carbon::now()->addMonths($months));
    }

    /**
     * Filter by company
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Filter by contract type
     */
    public function scopeByContractType(Builder $query, string $type): Builder
    {
        return $query->where('contract_type', $type);
    }

    /**
     * Filter by status
     */
    public function scopeByStatus(Builder $query, int $statusId): Builder
    {
        return $query->where('status_id', $statusId);
    }

    // ==================
    // Accessors
    // ==================

    /**
     * Get formatted contract status based on end date
     */
    public function getContractStatusAttribute(): string
    {
        if (!$this->end_contract_date) {
            return 'No end date';
        }

        $now = Carbon::now();
        $fourMonthsFromNow = $now->copy()->addMonths(4);
        $endDate = Carbon::parse($this->end_contract_date);

        if ($endDate->lessThan($now)) {
            return 'Expired';
        }

        if ($endDate->lessThan($fourMonthsFromNow)) {
            return 'Expiring soon';
        }

        return 'Active';
    }
}
