<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'candidate_id',
        'dateFrom',
        'dateTo',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }
}
