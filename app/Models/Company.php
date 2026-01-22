<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Company
 *
 * @property int $id
 * @property string $nameOfCompany
 * @property string $nameOfCompanyLatin
 * @property string $address
 * @property string $website
 * @property string $phoneNumber
 * @property string $EIK
 * @property string $contactPerson
 * @property string $EGN
 * @property string $dateBornDirector
 * @property string $companyCity
 * @property int $industry_id
 * @property string $foreignersLC12
 * @property string $description
 * @property string $nameOfContactPerson
 * @property string $phoneOfContactPerson
 * @property string $director_idCard
 * @property string $director_date_of_issue_idCard
 * @property float $commissionRate
 * @property string $logoPath
 * @property string $logoName
 * @property string $stampPath
 * @property string $stampName
 * @property mixed $employedByMonths
 */
class Company extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nameOfCompany',
        'nameOfCompanyLatin',
        'address',
        'website',
        'phoneNumber',
        'EIK',
        'contactPerson',
        'EGN',
        'dateBornDirector',
        'companyCity',
        'industry_id',
        'foreignersLC12',
        'description',
        'nameOfContactPerson',
        'phoneOfContactPerson',
        'director_idCard',
        'director_date_of_issue_idCard',
        'commissionRate',
        'logoPath',
        'logoName',
        'stampPath',
        'stampName',
        'employedByMonths',
        'companyPhone',

    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'employedByMonths' => 'array',
        'commissionRate' => 'decimal:2'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['default_email'];

    /**
     * Get the industry that owns the company.
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * Get the candidates for the company.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Get the month companies for the company.
     */
    public function monthCompanies(): HasMany
    {
        return $this->hasMany(MonthCompany::class, 'company_id');
    }

    /**
     * Get the company addresses for the company.
     */
    public function company_addresses(): HasMany
    {
        return $this->hasMany(CompanyAdress::class, 'company_id');
    }

    /**
     * Get the files for the company.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'company_id');
    }

    /**
     * Get the service contracts for the company.
     */
    public function serviceContracts(): HasMany
    {
        return $this->hasMany(CompanyServiceContract::class, 'company_id');
    }

    /**
     * Scope a query to only include companies with specific EIK.
     */
    public function scopeByEik($query, $eik)
    {
        return $query->where('EIK', $eik);
    }

    /**
     * Scope a query to only include companies by industry.
     */
    public function scopeByIndustry($query, $industryId)
    {
        return $query->where('industry_id', $industryId);
    }

    /**
     * Get the full logo URL.
     */
    public function getLogoUrlAttribute()
    {
        return $this->logoPath ? asset('storage/' . $this->logoPath) : null;
    }

    /**
     * Get the full stamp URL.
     */
    public function getStampUrlAttribute()
    {
        return $this->stampPath ? asset('storage/' . $this->stampPath) : null;
    }

    public function companyFiles()
    {
        return $this->hasMany(CompanyFile::class, 'company_id');
    }

    /**
     * Get the company jobs for the company.
     */
    public function companyJobs(): HasMany
    {
        return $this->hasMany(CompanyJob::class, 'company_id');
    }

    /**
     * Get the emails for the company.
     */
    public function companyEmails(): HasMany
    {
        return $this->hasMany(CompanyEmail::class);
    }

    /**
     * Get the default email for the company.
     * Use $company->default_email
     */
    public function getDefaultEmailAttribute()
    {
        // Check internal collection if loaded
        $default = $this->companyEmails->firstWhere('is_default', true);
        
        // If not found (maybe no default set, though should be), fallback to first
        if (!$default) {
            $default = $this->companyEmails->first();
        }
        
        return $default ? $default->email : null;
    }
}
