<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_id',
        'status_arrival_id',
        'status_description',
        'status_date'
    ];

    public function arrival()
    {
        return $this->belongsTo(Arrival::class);
    }

    public function statusArrival()
    {
        return $this->belongsTo(StatusArrival::class);
    }
}
