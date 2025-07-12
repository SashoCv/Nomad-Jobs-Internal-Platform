<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'status_id', 'type_id', 'company_id', 'position_id', 'user_id', 'case_id', 'agent_id',
        'gender', 'email', 'nationality', 'date', 'phoneNumber', 'address', 'passport',
        'fullName', 'fullNameCyrillic', 'birthday', 'placeOfBirth', 'country', 'area',
        'areaOfResidence', 'addressOfResidence', 'periodOfResidence', 'passportValidUntil',
        'passportIssuedBy', 'passportIssuedOn', 'addressOfWork', 'nameOfFacility',
        'education', 'specialty', 'qualification', 'contractExtensionPeriod', 'salary',
        'workingTime', 'workingDays', 'martialStatus', 'contractPeriod', 'contractType',
        'dossierNumber', 'notes', 'addedBy', 'quartal', 'seasonal', 'contractPeriodDate',
        'contractPeriodNumber', 'startContractDate', 'endContractDate', 'passportPath',
        'passportName', 'personPicturePath', 'personPictureName'
    ];

    protected $casts = [
        'date' => 'date',
        'birthday' => 'date',
        'passportValidUntil' => 'date',
        'passportIssuedOn' => 'date',
        'startContractDate' => 'date',
        'endContractDate' => 'date',
        'contractPeriodDate' => 'date',
        'salary' => 'decimal:2',
        'contractPeriodNumber' => 'integer',
    ];

    const TYPE_CANDIDATE = 1;
    const TYPE_EMPLOYEE = 2;

    const CONTRACT_TYPE_90_DAYS = '90days';
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
        return $this->hasMany(Statushistory::class);
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(Statushistory::class)->latestOfMany('statusDate');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
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

    public function calculateQuartal(Carbon $date): string
    {
        $year = $date->year;
        $month = $date->month;

        $quartal = match (true) {
            $month >= 1 && $month <= 3 => 1,
            $month >= 4 && $month <= 6 => 2,
            $month >= 7 && $month <= 9 => 3,
            $month >= 10 && $month <= 12 => 4,
        };

        return $quartal . '/' . $year;
    }

    public function calculateSeason(Carbon $date): string
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

        return $season . '/' . $seasonYear;
    }

    public function calculateContractEndDate(Carbon $startDate, string $contractPeriod): ?Carbon
    {
        preg_match('/\d+/', $contractPeriod, $matches);
        $period = isset($matches[0]) ? (int) $matches[0] : null;

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

        if ($this->endContractDate->lessThan($now)) {
            return 'Expired';
        }

        if ($this->endContractDate->lessThan($fourMonthsFromNow)) {
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
}
