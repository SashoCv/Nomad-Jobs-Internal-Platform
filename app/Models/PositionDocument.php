<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'name',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
