<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCategory extends Model
{
    use HasFactory;

    protected $table = 'company_categories';

    protected $fillable = [
        'role_id',
        'company_id',
        'companyNameCategory',
        'allowed_roles',
        'description'
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];
}
