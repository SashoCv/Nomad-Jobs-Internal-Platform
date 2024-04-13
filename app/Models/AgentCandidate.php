<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'company_job_id',
        'candidate_id',
    ];
}
