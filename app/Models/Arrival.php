<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrival extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arrivals';

    protected $fillable = [
        'company_id',
        'candidate_id',
        'arrival_date',
        'arrival_time',
        'arrival_location',
        'arrival_flight',
        'where_to_stay',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }
}
