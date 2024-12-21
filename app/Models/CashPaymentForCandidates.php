<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashPaymentForCandidates extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'user_id',
        'amount',
        'purposeOfPayment',
        'isPaid',
        'paymentDate'
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
