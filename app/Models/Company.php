<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function company_adresses(): HasMany
    {
        return $this->hasMany(CompanyAdress::class, 'company_id');
    }
}
