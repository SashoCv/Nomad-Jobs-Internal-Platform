<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'company_id',
        'company_service_contract_id',
        'contract_service_type_id',
        'statusName',
        'statusDate',
        'price',
        'invoiceStatus',
        'notes',
    ];

    const INVOICE_STATUS_INVOICED = 'invoiced';
    const INVOICE_STATUS_NOT_INVOICED = 'not_invoiced';
    const INVOICE_STATUS_REJECTED = 'rejected';

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyServiceContract()
    {
        return $this->belongsTo(CompanyServiceContract::class);
    }

    public function contractServiceType()
    {
        return $this->belongsTo(ContractServiceType::class);
    }
}
