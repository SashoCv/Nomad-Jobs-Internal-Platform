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
        'hr_employee_id',
        'company_admin_contact',
        'power_of_attorney',
        'personnel_references',
        'accommodation_address',
        'workplace_address',
        'hr_notes',
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
        return $this->hasOneThrough(
            User::class,
            AsignCandidateToNomadOffice::class,
            'candidate_id', // Foreign key on asign_candidate_to_nomad_offices table
            'id', // Foreign key on users table
            'candidate_id', // Local key on agent_candidates table
            'nomad_office_id' // Local key on asign_candidate_to_nomad_offices table
        );
    }

    public function hrAssignment()
    {
        return $this->hasOne(AsignCandidateToNomadOffice::class, 'candidate_id', 'candidate_id');
    }

    public function details()
    {
        return $this->hasOne(AgentCandidateDetail::class);
    }
}

