<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentCandidateDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_candidate_id',
        'powerOfAttorney',
        'personnelReferences',
        'accommodationAddress',
        'notes',
    ];

    protected $casts = [
        'powerOfAttorney' => 'boolean',
        'personnelReferences' => 'boolean',
        'accommodationAddress' => 'boolean',
    ];

    public function agentCandidate()
    {
        return $this->belongsTo(AgentCandidate::class);
    }
}
