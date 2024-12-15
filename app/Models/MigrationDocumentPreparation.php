<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MigrationDocumentPreparation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'user_id',
        'medicalCertificate',
        'dateOfPreparationOnDocument',
        'submissionDate',
        'authorization',
        'residenceDeclaration',
        'justificationAuthorization',
        'declarationOfForeigners',
        'notarialDeed',
        'conditionsMetDeclaration',
        'jobDescription',
        'employmentContract'
    ];

    protected $casts = [
        'authorization' => 'boolean',
        'residenceDeclaration' => 'boolean',
        'justificationAuthorization' => 'boolean',
        'declarationOfForeigners' => 'boolean',
        'notarialDeed' => 'boolean',
        'conditionsMetDeclaration' => 'boolean',
        'jobDescription' => 'boolean',
        'employmentContract' => 'boolean'
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
