<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'nameOfStatus',
        'order',
        'showOnHomePage',
    ];

    protected $casts = [
        'showOnHomePage' => 'boolean',
    ];
}
