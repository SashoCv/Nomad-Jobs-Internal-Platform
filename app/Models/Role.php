<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['roleName'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    // Role constants
    const GENERAL_MANAGER = 1;
    const MANAGER = 2;
    const COMPANY_USER = 3;
    const AGENT = 4;
    const COMPANY_OWNER = 5;
    const OFFICE = 6;
    const HR = 7;
    const OFFICE_MANAGER = 8;
    const RECRUITERS = 9;
    const FINANCE = 10;
}
