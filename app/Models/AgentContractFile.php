<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentContractFile extends Model
{
    use HasFactory;

    protected $table = 'agent_contract_files';

    protected $fillable = [
        'agent_service_contract_id',
        'filePath',
        'fileName',
    ];

    public function agentServiceContract(): BelongsTo
    {
        return $this->belongsTo(AgentServiceContract::class, 'agent_service_contract_id');
    }
}
