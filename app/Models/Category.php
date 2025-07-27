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
        'role_id',
        'isGenerated',
    ];
}
