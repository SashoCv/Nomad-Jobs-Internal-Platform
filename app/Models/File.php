<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    public function candidate()
    {
        return $this->belongsTo(Candidate::class,'candidate_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    protected $casts = [
        'files' => 'array',
    ];
}
