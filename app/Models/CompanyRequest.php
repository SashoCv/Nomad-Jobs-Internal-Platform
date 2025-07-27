<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_job_id',
        'approved',
        'description',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function companyJob()
    {
        return $this->belongsTo(CompanyJob::class);
    }
}
