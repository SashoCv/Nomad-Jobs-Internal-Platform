<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'date',
        'time',
        'candidate_id',
        'company_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time' => 'datetime:H:i',
    ];

    const TYPE_INTERVIEW = 'interview';
    const TYPE_ARRIVAL = 'arrival';
    const TYPE_CONTRACT_EXPIRY = 'contract_expiry';
    const TYPE_INSURANCE_EXPIRY = 'insurance_expiry';
    const TYPE_VISA_EXPIRY = 'visa_expiry';

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
