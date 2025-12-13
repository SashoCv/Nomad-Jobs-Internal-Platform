<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentContractPricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agent_contract_pricing';

    protected $fillable = [
        'agent_service_contract_id',
        'agent_service_type_id',
        'status_id',
        'price',
        'description',
        'countryScopeType',
        'countryScopeIds',
        'companyScopeType',
        'companyScopeIds',
    ];

    protected $casts = [
        'countryScopeIds' => 'array',
        'companyScopeIds' => 'array',
        'price' => 'decimal:2',
    ];

    public function agentServiceContract()
    {
        return $this->belongsTo(AgentServiceContract::class);
    }

    public function agentServiceType()
    {
        return $this->belongsTo(AgentServiceType::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
