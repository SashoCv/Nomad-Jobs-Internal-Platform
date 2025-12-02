<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignCandidateToNomadOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'nomad_office_id',
        'candidate_id',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id')->select(['id', 'firstName', 'lastName', 'email']);
    }

    public function nomadOffice()
    {
        return $this->belongsTo(User::class, 'nomad_office_id')->select(['id', 'firstName', 'lastName', 'email']);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
