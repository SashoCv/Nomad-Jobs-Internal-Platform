<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    const ARRIVAL_DOCUMENTS = 'Arrival Documents / Документи за пристигане';

    protected $fillable = [
        'candidate_id',
        'nameOfCategory',
        'description',
        'isGenerated',
    ];

    public function visibleToRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'category_role');
    }

    public function isVisibleToRole(int $roleId): bool
    {
        if ($this->relationLoaded('visibleToRoles')) {
            return $this->visibleToRoles->contains('id', $roleId);
        }

        return $this->visibleToRoles()->where('roles.id', $roleId)->exists();
    }
}
