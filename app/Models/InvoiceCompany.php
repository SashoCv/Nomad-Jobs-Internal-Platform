<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoice_companies';

    protected $fillable = [
        'company_id',
        'invoice_number',
        'invoice_date',
        'status',
        'invoice_amount',
        'due_date',
        'payment_date',
        'payment_amount',
        'is_paid',
    ];

    public function company():BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function itemInvoice(): HasMany
    {
        return $this->hasMany(ItemInvoice::class, 'invoice_companies_id');
    }
}
