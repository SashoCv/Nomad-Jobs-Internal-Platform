<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyJob extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changeLogs()
    {
        return $this->hasMany(ChangeLog::class, 'record_id')->where('tableName', 'company_jobs');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function agentCandidates()
    {
        return $this->hasMany(AgentCandidate::class, 'company_job_id');
    }
}
