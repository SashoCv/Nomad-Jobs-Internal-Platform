<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'status_id', 'type_id', 'company_id', 'position_id', 'user_id', 'case_id', 'agent_id',
        'gender', 'email', 'nationality', 'date', 'phoneNumber', 'address', 'passport',
        'fullName', 'fullNameCyrillic', 'birthday', 'placeOfBirth', 'country_id', 'area',
        'areaOfResidence', 'addressOfResidence', 'periodOfResidence', 'passportValidUntil',
        'passportIssuedBy', 'passportIssuedOn', 'addressOfWork', 'nameOfFacility',
        'education', 'specialty', 'qualification', 'contractExtensionPeriod', 'salary',
        'workingTime', 'workingDays', 'martialStatus', 'contractPeriod', 'contractType',
        'dossierNumber', 'notes', 'addedBy', 'quartal', 'seasonal', 'contractPeriodDate',
        'contractPeriodNumber', 'startContractDate', 'endContractDate', 'passportPath',
        'passportName', 'personPicturePath', 'personPictureName', 'company_adresses_id',
        'deleted_by',
        // CV fields
        'height', 'weight', 'chronic_diseases', 'country_of_visa_application',
        'has_driving_license', 'driving_license_category', 'driving_license_expiry', 'driving_license_country',
        'english_level', 'russian_level', 'other_language', 'other_language_level',
        'children_info'
    ];

    protected $appends = ['workAddressCity'];

    /**
     * Get the city from the selected company work address.
     * This is a virtual property used in documents and the frontend.
     */
    public function getWorkAddressCityAttribute(): ?string
    {
        // Check if the relationship is loaded and exists
        if ($this->companyAddress) {
            $city = $this->companyAddress->city;

            // Handle both City model relationship and legacy string column
            if ($city instanceof \App\Models\City) {
                return $city->name;
            }

            return is_string($city) ? $city : null;
        }

        return null;
    }

    protected $casts = [
        'date' => 'date:Y-m-d',
        'has_driving_license' => 'boolean',
    ];

    const TYPE_CANDIDATE = 1;
    const TYPE_EMPLOYEE = 2;

    const CONTRACT_TYPE_90_DAYS = '90 дни';
    const CONTRACT_TYPE_YEARLY = 'indefinite';

    const CONTRACT_TYPE_9_MONTHS = '9months';

    const SEASON_SPRING = 'spring';
    const SEASON_SUMMER = 'summer';
    const SEASON_AUTUMN = 'autumn';
    const SEASON_WINTER = 'winter';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function contractCandidate()
    {
        return $this->belongsTo(ContractCandidate::class, 'contract_candidates_id');
    }


    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(Statushistory::class)
            ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
            ->select('statushistories.*', 'statuses.order')
            ->orderBy('statuses.order', 'desc');
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(Statushistory::class)
            ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
            ->select('statushistories.*', 'statuses.order')
            ->orderBy('statuses.order', 'desc');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function companyAddress()
    {
        return $this->belongsTo(CompanyAdress::class, 'company_adresses_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function agentCandidates()
    {
        return $this->hasMany(AgentCandidate::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function cases()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function asignCandidateToNomadOffice()
    {
        return $this->hasMany(AsignCandidateToNomadOffice::class);
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function experience()
    {
        return $this->hasMany(Experience::class);
    }

    public function medicalInsurance()
    {
        return $this->hasMany(MedicalInsurance::class);
    }

    public function arrival()
    {
        return $this->hasOne(Arrival::class);
    }

    public function visas()
    {
        return $this->hasMany(CandidateVisa::class);
    }

    public function currentVisa()
    {
        return $this->hasOne(CandidateVisa::class)->latestOfMany('end_date');
    }

    /**
     * Get the passport record for this candidate.
     * This is the source of truth for passport data.
     */
    public function passport(): HasOne
    {
        return $this->hasOne(CandidatePassport::class);
    }

    public function cvPhotos()
    {
        return $this->hasMany(CandidateCvPhoto::class);
    }

    public function workplacePhotos()
    {
        return $this->hasMany(CandidateCvPhoto::class)->where('type', CandidateCvPhoto::TYPE_WORKPLACE)->orderBy('sort_order');
    }

    public function diplomaPhotos()
    {
        return $this->hasMany(CandidateCvPhoto::class)->where('type', CandidateCvPhoto::TYPE_DIPLOMA)->orderBy('sort_order');
    }

    public function drivingLicensePhoto()
    {
        return $this->hasOne(CandidateCvPhoto::class)->where('type', CandidateCvPhoto::TYPE_DRIVING_LICENSE);
    }

    // ==================
    // Contract Relationships (NEW)
    // ==================

    /**
     * Get all contracts for this candidate (person profile)
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(CandidateContract::class)->orderBy('contract_period_number', 'desc');
    }

    /**
     * Get the currently active contract
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(CandidateContract::class)
            ->where('is_active', true)
            ->latestOfMany('contract_period_number');
    }

    /**
     * Get the most recent contract (active or not)
     */
    public function latestContract(): HasOne
    {
        return $this->hasOne(CandidateContract::class)
            ->latestOfMany('contract_period_number');
    }

    /**
     * Get the count of contracts for this candidate
     */
    public function getContractsCountAttribute(): int
    {
        return $this->contracts()->count();
    }

    // Scopes
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus(Builder $query, int $statusId): Builder
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeByType(Builder $query, int $typeId): Builder
    {
        return $query->where('type_id', $typeId);
    }

    public function scopeCandidates(Builder $query): Builder
    {
        return $query->where('type_id', self::TYPE_CANDIDATE);
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('type_id', self::TYPE_EMPLOYEE);
    }

    public function scopeContractExpiring(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('endContractDate', '<=', $date);
    }

    public function scopeSeasonalContracts(Builder $query): Builder
    {
        return $query->where('contractType', self::CONTRACT_TYPE_90_DAYS);
    }

    public function scopeByQuartal(Builder $query, string $quartal): Builder
    {
        return $query->where('quartal', $quartal);
    }

    public function scopeBySeason(Builder $query, string $season): Builder
    {
        return $query->where('seasonal', 'like', $season . '%');
    }

    // Mutators & Accessors
    public function setQuartalAttribute(): void
    {
        if ($this->date) {
            $this->attributes['quartal'] = $this->calculateQuartal($this->date);
        }
    }

    public function setSeasonalAttribute(): void
    {
        if ($this->date && $this->contractType === self::CONTRACT_TYPE_90_DAYS) {
            $this->attributes['seasonal'] = $this->calculateSeason($this->date);
        }
    }

    public function setContractPeriodDateAttribute(): void
    {
        if ($this->date && $this->contractPeriod) {
            $this->attributes['contractPeriodDate'] = $this->calculateContractEndDate($this->date, $this->contractPeriod);
        }
    }

    // Helper Methods
    public function isCandidate(): bool
    {
        return $this->type_id === self::TYPE_CANDIDATE;
    }

    public function isEmployee(): bool
    {
        return $this->type_id === self::TYPE_EMPLOYEE;
    }

    public function isSeasonalContract(): bool
    {
        return $this->contractType === self::CONTRACT_TYPE_90_DAYS;
    }

    public function promoteToEmployee(): void
    {
        $this->update([
            'type_id' => self::TYPE_EMPLOYEE,
            'status_id' => 10, // Worker status
        ]);
    }

    public function calculateQuartal($date): string
    {
        $year = $date->year;
        $month = $date->month;

        Log::info('year: ' . $year . ', month: ' . $month);

        $quartal = match (true) {
            $month >= 1 && $month <= 3 => 1,
            $month >= 4 && $month <= 6 => 2,
            $month >= 7 && $month <= 9 => 3,
            $month >= 10 && $month <= 12 => 4,
        };

        return $quartal . '/' . $year;
    }

    public function calculateSeason($date): string
    {
        $year = $date->year;
        $month = $date->month;

        $season = match (true) {
            $month >= 5 && $month <= 9 => self::SEASON_SUMMER,
            $month >= 11 || $month <= 2 => self::SEASON_WINTER,
            $month >= 2 && $month <= 5 => self::SEASON_SPRING,
            $month >= 8 && $month <= 11 => self::SEASON_AUTUMN,
        };

        $seasonYear = ($season === self::SEASON_WINTER && $month <= 2) ? $year - 1 : $year;

        Log::info('Season: ' . $season . ', Year: ' . $seasonYear);
        return $season . '/' . $seasonYear;
    }

    public function calculateContractEndDate(Carbon $startDate, string $contractPeriod): ?Carbon
    {
        preg_match('/\d+/', $contractPeriod, $matches);
        $period = isset($matches[0]) ? (int) $matches[0] : null;

        Log::info('Contract period: ' . $contractPeriod . ', Period: ' . $period);
        return $period ? $startDate->copy()->addYears($period) : null;
    }

    // Attribute Accessors
    public function getFullDisplayNameAttribute(): string
    {
        return $this->fullNameCyrillic ?: $this->fullName;
    }

    public function getContractStatusAttribute(): string
    {
        if (!$this->endContractDate) {
            return 'No end date';
        }

        $now = Carbon::now();
        $fourMonthsFromNow = $now->copy()->addMonths(4);
        $endDate = Carbon::parse($this->endContractDate);

        if ($endDate->lessThan($now)) {
            return 'Expired';
        }

        if ($endDate->lessThan($fourMonthsFromNow)) {
            return 'Expiring soon';
        }

        return 'Active';
    }

    public function getHasMedicalInsuranceAttribute(): bool
    {
        return $this->medicalInsurance()->exists();
    }

    public function getHasArrivalAttribute(): bool
    {
        return $this->arrival()->exists();
    }

    public function getStatusDateAttribute()
    {
        if (!$this->status_id) {
            return null;
        }

        $statusHistory = $this->statusHistories()
            ->where('candidate_id', $this->id)
            ->where('status_id', $this->status_id)
            ->orderBy('statusDate', 'desc')
            ->first();

        return $statusHistory ? $statusHistory->statusDate : null;
    }
}
