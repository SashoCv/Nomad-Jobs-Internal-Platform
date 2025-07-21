<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // Permission constants
    const COMPANIES_VIEW = 'companies.view';
    const COMPANIES_CREATE = 'companies.create';
    const COMPANIES_EDIT = 'companies.edit';
    const COMPANIES_DELETE = 'companies.delete';
    const COMPANIES_CONTRACTS = 'companies.contracts';

    const USERS_VIEW = 'users.view';
    const USERS_CREATE = 'users.create';
    const USERS_EDIT = 'users.edit';
    const USERS_DELETE = 'users.delete';
    const USERS_CREATE_COMPANIES = 'users.create.companies';
    const USERS_CREATE_AGENTS = 'users.create.agents';

    const CANDIDATES_VIEW = 'candidates.view';
    const CANDIDATES_CREATE = 'candidates.create';
    const CANDIDATES_EDIT = 'candidates.edit';
    const CANDIDATES_DELETE = 'candidates.delete';

    const JOBS_VIEW = 'jobs.view';
    const JOBS_CREATE = 'jobs.create';
    const JOBS_EDIT = 'jobs.edit';
    const JOBS_DELETE = 'jobs.delete';

    const FINANCE_VIEW = 'finance.view';
    const FINANCE_CREATE = 'finance.create';
    const FINANCE_EDIT = 'finance.edit';
    const FINANCE_DELETE = 'finance.delete';
}
