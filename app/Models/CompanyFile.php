<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFile extends Model
{
    use HasFactory;

    protected $table = 'company_files';

    protected $fillable = [
        'company_id',
        'company_category_id',
        'fileName',
        'filePath',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function categoryForCompany()
    {
        return $this->belongsTo(CompanyCategory::class, 'company_category_id');
    }
}
