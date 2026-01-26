<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'position',
        'responsibilities',
        'start_date',
        'end_date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
