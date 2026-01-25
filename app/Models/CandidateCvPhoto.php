<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCvPhoto extends Model
{
    use HasFactory;

    public const TYPE_WORKPLACE = 'workplace';
    public const TYPE_DIPLOMA = 'diploma';
    public const TYPE_DRIVING_LICENSE = 'driving_license';

    protected $fillable = [
        'candidate_id',
        'type',
        'file_path',
        'file_name',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }
}
