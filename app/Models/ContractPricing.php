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
        'currency',
        'status_id',
        'description',
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
}
