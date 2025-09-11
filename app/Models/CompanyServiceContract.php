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

    public const AGREEMENT_TYPE_ERPR = 'erpr';
    public const AGREEMENT_TYPE_90DAYS = '90days';

    protected $fillable = [
        'company_id',
        'contractNumber',
        'agreement_type',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contractPricing()
    {
        return $this->hasMany(ContractPricing::class, 'company_service_contract_id');
    }

    /**
     * Scope to get only active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Set this contract as active and deactivate others for the same company
     */
    public function setAsActive()
    {
        // First deactivate other active contracts for this company
        self::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_EXPIRED]);

        // Then set this contract as active
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get the active contract for a company
     * 
     * @param int $companyId
     * @return CompanyServiceContract|null
     */
    public static function getActiveContract($companyId)
    {
        return self::where('company_id', $companyId)
            ->active()
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Check if this is the active contract for the company
     * 
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
