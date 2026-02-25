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
    const TYPE_PASSPORT_EXPIRY = 'passport_expiry';
    const TYPE_RECEIVED_VISA = 'received_visa';
    const TYPE_ERPR_PROCEDURE = 'erpr_procedure';
    const TYPE_ERPR_LETTER = 'erpr_letter';
    const TYPE_ERPR_PHOTO = 'erpr_photo';
    const TYPE_HIRED = 'hired';
    const TYPE_VISA_APPOINTMENT = 'visa_appointment';

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
