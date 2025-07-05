<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }


    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(Statushistory::class);
    }

    public function latestStatusHistory()
    {
        return $this->hasOne(Statushistory::class)->latestOfMany('statusDate');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function agentCandidates()
    {
        return $this->hasMany(AgentCandidate::class);
    }

    public function cases()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function asignCandidateToNomadOffice()
    {
        return $this->hasMany(AsignCandidateToNomadOffice::class);
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function experience()
    {
        return $this->hasMany(Experience::class);
    }

    public function medicalInsurance()
    {
        $insurance = $this->hasMany(MedicalInsurance::class)->get();

        return $insurance->isEmpty() ? [] : $insurance;
    }

    public function arrival()
    {
        return $this->hasOne(Arrival::class);
    }
}
