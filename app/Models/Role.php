<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\CompanyCategory;

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
        return $this->permissions()->where('slug', $permission)->exists();
    }

    public function hasAnyPermission($permissions)
    {
        return $this->permissions()->whereIn('slug', $permissions)->exists();
    }

    public function visibleCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_role');
    }

    public function visibleCompanyCategories(): BelongsToMany
    {
        return $this->belongsToMany(CompanyCategory::class, 'company_category_role');
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }
        
        return $this->permissions()->syncWithoutDetaching($permission->id);
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
