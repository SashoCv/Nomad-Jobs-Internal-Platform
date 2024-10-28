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

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function companyJob()
    {
        return $this->belongsTo(CompanyJob::class);
    }

    public function statusForCandidateFromAgent()
    {
        return $this->belongsTo(StatusForCandidateFromAgent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

