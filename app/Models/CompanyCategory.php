<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyCategory extends Model
{
    use HasFactory;

    protected $table = 'company_categories';

    protected $fillable = [
        'company_id',
        'companyNameCategory',
        'description',
    ];

    public function visibleToRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_category_role');
    }

    public function isVisibleToRole(int $roleId): bool
    {
        if ($this->relationLoaded('visibleToRoles')) {
            return $this->visibleToRoles->contains('id', $roleId);
        }

        return $this->visibleToRoles()->where('roles.id', $roleId)->exists();
    }
}
