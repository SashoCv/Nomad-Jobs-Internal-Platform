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
        'country_scope_type',
        'country_scope_ids',
    ];

    protected $casts = [
        'country_scope_ids' => 'array',
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
        $type = $this->country_scope_type ?? 'all';
        $ids = $this->country_scope_ids ?? [];

        if ($type === 'all' || empty($ids)) {
            return 'За всички държави';
        }

        $countryNames = \App\Models\Country::whereIn('id', $ids)->pluck('name')->implode(', ');

        return match($type) {
            'include' => "Само: {$countryNames}",
            'exclude' => "Освен: {$countryNames}",
            default => 'За всички държави',
        };
    }
}
