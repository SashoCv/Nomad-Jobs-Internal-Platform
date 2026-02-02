<?php

namespace App\Models;

use App\Models\Traits\HasContractType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CandidateContract extends Model
{
    use HasFactory, SoftDeletes, HasContractType;

    protected $table = 'candidate_contracts';

    protected $fillable = [
        'candidate_id',
        'company_id',
        'position_id',
        'status_id',
        'type_id',
        'contract_type',
        'contract_type_id',
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
        'is_extension',
    ];

    protected $casts = [
        'start_contract_date' => 'date',
        'end_contract_date' => 'date',
        'contract_period_date' => 'date',
        'date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
        'is_extension' => 'boolean',
    ];

    /**
     * Salary attribute: accepts European format input (comma as decimal separator).
     * Accepts: "620,20" or "620.20" → stores as 620.20
     * Returns: numeric value (frontend handles display formatting)
     */
    protected function salary(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value !== null ? (float) str_replace(',', '.', (string) $value) : null,
        );
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function contract_type(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function companyAddress(): BelongsTo
    {
        return $this->belongsTo(CompanyAdress::class, 'company_adresses_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cases(): BelongsTo
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'contract_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(Statushistory::class, 'contract_id');
    }

    public function agentCandidates(): HasMany
    {
        return $this->hasMany(AgentCandidate::class, 'contract_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'contract_id');
    }

    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class, 'contract_id');
    }

    public function visas(): HasMany
    {
        return $this->hasMany(CandidateVisa::class, 'contract_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringSoon(Builder $query, int $months = 4): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('end_contract_date')
            ->whereDate('end_contract_date', '<=', Carbon::now()->addMonths($months));
    }

    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByContractType(Builder $query, string $type): Builder
    {
        return $query->where('contract_type', $type);
    }

    public function scopeByStatus(Builder $query, int $statusId): Builder
    {
        return $query->where('status_id', $statusId);
    }

    public function getContractStatusAttribute(): string
    {
        if (! $this->end_contract_date) {
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
