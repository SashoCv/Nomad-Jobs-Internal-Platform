<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statushistory extends Model
{
    use HasFactory;


    protected $fillable = [
        'candidate_id',
        'status_id',
        'statusDate',
        'description',
    ];

    protected $casts = [
        'statusDate' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
