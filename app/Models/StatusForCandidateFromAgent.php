<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusForCandidateFromAgent extends Model
{
    use HasFactory, SoftDeletes;

    const ADDED = 1;
    const FOR_INTERVIEW = 2;
    const APPROVED = 3;
    const UNSUITABLE = 4;
    const RESERVE = 5;
    const REJECTED = 6;

    protected $fillable = [
        'name',
        'order',
        'show_for_companies',
    ];

    protected $casts = [
        'show_for_companies' => 'boolean',
    ];
}
