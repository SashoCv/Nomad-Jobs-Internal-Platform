<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatePassport extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'passport_number',
        'issue_date',
        'expiry_date',
        'issuing_country',
        'file_path',
        'file_name',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
