<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'item_invoices';

    protected $fillable = [
        'invoice_companies_id',
        'items_for_invoices_id',
        'percentage',
        'price',
        'total',
        'amount',
    ];

    public function invoiceCompany(): BelongsTo
    {
        return $this->belongsTo(InvoiceCompany::class, 'invoice_companies_id');
    }

    public function itemForInvoice(): BelongsTo
    {
        return $this->belongsTo(ItemsForInvoices::class, 'items_for_invoices_id');
    }
}
