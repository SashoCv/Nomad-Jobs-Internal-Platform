<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractPricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contract_pricings';

    protected $fillable = [
        'company_service_contract_id',
        'contract_service_type_id',
        'price',
        'price_bgn',
        'status_id',
        'description',
        'country_scope',
    ];

    public function companyServiceContract(): BelongsTo
    {
        return $this->belongsTo(CompanyServiceContract::class, 'company_service_contract_id');
    }

    public function contractServiceType(): BelongsTo
    {
        return $this->belongsTo(ContractServiceType::class, 'contract_service_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get the country scope display name
     *
     * @return string
     */
    public function getCountryScopeDisplayAttribute(): string
    {
        return match($this->country_scope) {
            'all_countries' => 'За всички държави',
            'india_nepal_only' => 'Само за Индия и Непал',
            'except_india_nepal' => 'За всички освен Индия и Непал',
            default => 'За всички държави',
        };
    }

    /**
     * Scope a query to only include pricings for specific countries
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCountryScope($query, string $scope)
    {
        return $query->where('country_scope', $scope);
    }
}
