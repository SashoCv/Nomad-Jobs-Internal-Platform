<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    public function type()
    {
        return $this->belongsTo(Type::class,'type_id');
    }


    public function status()
    {
        return $this->belongsTo(Status::class,'status_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function categories()
    {
        return $this->hasManyThrough(Category::class,File::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
}
