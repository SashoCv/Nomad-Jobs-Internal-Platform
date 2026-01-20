<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyEmail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'email',
        'is_default',
        'is_notification_recipient',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_notification_recipient' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
