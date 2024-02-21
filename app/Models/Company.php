<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }
    
        public function month_companies()
    {
        return $this->hasMany(MonthCompany::class, 'company_id');
    }
}
