<?php

namespace App\Models;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;


    public function person()
    {
        return $this->belongsTo(Candidate::class,'candidate_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
