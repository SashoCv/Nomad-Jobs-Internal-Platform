<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalPricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'arrival_id',
        'airplane_price',
        'bus_price',
        'price',
        'margin',
        'total',
        'billed',
        'isTransportCoveredByNomad'
    ];

    protected $casts = [
        'airplane_price' => 'float',
        'bus_price' => 'float',
        'price' => 'float',
        'margin' => 'decimal:2',
        'total' => 'float',
        'billed' => 'boolean',
        'isTransportCoveredByNomad' => 'integer',
    ];


    public function arrival()
    {
        return $this->belongsTo(Arrival::class, 'arrival_id');
    }
}
