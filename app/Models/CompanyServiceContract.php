<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyServiceContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_service_contracts';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    public const AGREEMENT_TYPE_STANDARD = 'standard';
    // here i need more and base on this agreement type i need logic

    protected $fillable = [
        'company_id',
        'contractNumber',
        'agreement_type',
        'status',
        'contractDate',
    ];

    protected $casts = [
        'contractDate' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contractPricing()
    {
        return $this->hasMany(ContractPricing::class, 'company_service_contract_id');
    }
}
