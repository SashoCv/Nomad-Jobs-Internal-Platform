<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentServiceType extends Model
{
    use HasFactory;

    protected $table = 'agent_service_types';

    protected $fillable = [
        'name',
    ];
}
