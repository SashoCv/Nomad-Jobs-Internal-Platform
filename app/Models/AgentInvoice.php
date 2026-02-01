<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'company_id',
        'agent_id',
        'agent_service_contract_id',
        'serviceTypeName',
        'statusName',
        'statusDate',
        'price',
        'invoiceStatus',
        'notes',
        'invoice_number',
    ];

    const INVOICE_STATUS_INVOICED = 'invoiced';
    const INVOICE_STATUS_NOT_INVOICED = 'not_invoiced';
    const INVOICE_STATUS_REJECTED = 'rejected';
    const INVOICE_STATUS_PAID = 'paid';

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function agentServiceContract()
    {
        return $this->belongsTo(AgentServiceContract::class);
    }
}
