<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'category_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type'
    ];

    public function candidates()
    {
        return $this->belongsTo(Candidate::class,'candidate_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }
}
