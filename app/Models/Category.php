<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'nameOfCategory',
        'description',
        'role_id',
        'isGenerated',
        'allowed_roles',
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];
}
