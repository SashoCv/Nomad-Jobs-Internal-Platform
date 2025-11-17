<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentCandidate extends Model
{
    use HasFactory, SoftDeletes;

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

    public function hrPerson()
    {
        return $this->belongsTo(User::class, 'nomad_office_id');
    }

    public function details()
    {
        return $this->hasOne(AgentCandidateDetail::class);
    }
}

