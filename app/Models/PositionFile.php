<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'file_name',
        'file_path',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
