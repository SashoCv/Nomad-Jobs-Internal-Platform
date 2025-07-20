<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'change_logs';

    protected $fillable = [
        'tableName',
        'record_id',
        'fieldName',
        'oldValue',
        'newValue',
        'user_id',
        'company_id',
        'status',
        'isApplied',
    ];

    protected $casts = [
        'isApplied' => 'boolean',
    ];

    protected $appends = ['target_record'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getTargetRecordAttribute()
    {
        if ($this->tableName === 'company_jobs') {
            return CompanyJob::find($this->record_id);
        }

        if ($this->tableName === 'contract_pricing') {
            return ContractPricing::find($this->record_id);
        }

        return null;
    }
}
