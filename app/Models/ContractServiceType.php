<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractServiceType extends Model
{
    use HasFactory;

    protected $table = 'contract_service_types';

    protected $fillable = [
        'name',
    ];
}
