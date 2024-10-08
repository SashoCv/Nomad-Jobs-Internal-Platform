<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCompanyCandidate extends Model
{
    use HasFactory;

    protected $table = 'invoice_company_candidates';

    protected $fillable = [
        'candidate_id',
        'invoice_company_id',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function invoiceCompany()
    {
        return $this->belongsTo(InvoiceCompany::class, 'invoice_company_id');
    }
}
