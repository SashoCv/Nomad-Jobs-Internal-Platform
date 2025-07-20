<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'role_id',
        'company_id',
        'lastName',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
    
       public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    public function hasAnyPermission($permissions)
    {
        if (!$this->role) return false;
        
        foreach ($permissions as $permission) {
            if ($this->role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole($roleId)
    {
        return $this->role_id == $roleId;
    }

    public function hasAnyRole($roleIds)
    {
        return in_array($this->role_id, $roleIds);
    }
    
}
