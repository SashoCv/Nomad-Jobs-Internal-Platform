<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    const MIGRATION = 1;
    const RECEIVED_PERMISSION = 2;
    const SUBMITTED_AT_EMBASSY = 3;
    const RECEIVED_VISA = 4;
    const ARRIVED = 5;
    const PROCEDURE_FOR_ERPR = 6;
    const PHOTO_FOR_ERPR = 7;
    const TAKING_ERPR = 8;
    const HIRED = 9;
    const FINISHED_CONTRACT = 10;
    const TERMINATED_CONTRACT = 11;
    const REFUSED_MIGRATION = 12;
    const REFUSED_CANDIDATE = 13;
    const REFUSED_EMPLOYER = 14;
    const SENT_DOCUMENTS_FOR_VISA = 15;
    const LETTER_FOR_ERPR = 17;
    const ARRIVAL_EXPECTED = 18;
    const REFUSED_BY_MIGRATION_OFFICE = 19;

    protected $fillable = [
        'nameOfStatus',
        'order',
        'showOnHomePage',
    ];

    protected $casts = [
        'showOnHomePage' => 'boolean',
    ];
}
