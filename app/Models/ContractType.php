<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractType model (renamed from ContractCandidate)
 *
 * This model represents employment contract types (ERPR 1, ERPR 2, ERPR 3, 90 days, 9 months)
 * Table was renamed from contract_candidates to contract_types for clarity.
 */
class ContractType extends Model
{
    use HasFactory;

    protected $table = 'contract_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Contract type codes (slugs)
    public const ERPR1 = 'erpr1';
    public const ERPR2 = 'erpr2';
    public const ERPR3 = 'erpr3';
    public const DAYS_90 = '90days';
    public const MONTHS_9 = '9months';

    /**
     * Get candidates with this contract type
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'contract_type_id');
    }

    /**
     * Get contracts with this contract type
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(CandidateContract::class, 'contract_type_id');
    }

    /**
     * Get company jobs with this contract type
     */
    public function companyJobs(): HasMany
    {
        return $this->hasMany(CompanyJob::class, 'contract_type_id');
    }

    /**
     * Find by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get ID by slug
     */
    public static function getIdBySlug(string $slug): ?int
    {
        return static::where('slug', $slug)->value('id');
    }
}
