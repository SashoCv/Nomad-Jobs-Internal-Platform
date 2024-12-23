<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyAdress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_adresses';

    protected $fillable = [
        'company_id',
        'address',
        'city',
        'state',
        'zip_code',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
