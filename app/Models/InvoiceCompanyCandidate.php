<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCompanyCandidate extends Model
{
    use HasFactory;
    use SoftDeletes;

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
