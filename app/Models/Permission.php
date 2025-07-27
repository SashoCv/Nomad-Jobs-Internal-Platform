<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'module'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    const DASHBOARD_READ = 'dashboard:read';
    const HOME_READ = 'home:read';
    const HOME_FILTER = 'home:filter';
    const HOME_ARRIVALS = 'home:arrivals';
    const HOME_CHANGE_STATUS = 'home:change_status';

    const COMPANIES_READ = 'companies:read';
    const COMPANIES_CREATE = 'companies:create';
    const COMPANIES_UPDATE = 'companies:update';
    const COMPANIES_DELETE = 'companies:delete';
    const COMPANIES_CONTRACTS = 'companies:contracts';

    const COMPANIES_CONTRACTS_READ = 'companies_contracts:read';
    const COMPANIES_CONTRACTS_CREATE = 'companies_contracts:create';
    const COMPANIES_CONTRACTS_UPDATE = 'companies_contracts:update';
    const COMPANIES_CONTRACTS_DELETE = 'companies_contracts:delete';

    const INDUSTRIES_READ = 'industries:read';
    const INDUSTRIES_CREATE = 'industries:create';
    const INDUSTRIES_UPDATE = 'industries:update';
    const INDUSTRIES_DELETE = 'industries:delete';

    const COMPANY_JOB_REQUESTS_READ = 'requests:read';
    const COMPANY_JOB_REQUESTS_APPROVE = 'requests:approve';
    const COMPANY_JOB_REQUESTS_DELETE = 'requests:delete';

    const CANDIDATES_READ = 'candidates:read';
    const CANDIDATES_CREATE = 'candidates:create';
    const CANDIDATES_UPDATE = 'candidates:update';
    const CANDIDATES_DELETE = 'candidates:delete';
    const CANDIDATES_EXPORT = 'candidates:export';

    const AGENT_CANDIDATES_READ = 'agent_candidates:read';
    const AGENT_CANDIDATES_CREATE = 'agent_candidates:create';
    const AGENT_CANDIDATES_CHANGE_STATUS = 'agent_candidates:change_status';
    const AGENT_CANDIDATES_DELETE = 'agent_candidates:delete';

    const MULTI_APPLICANT_GENERATOR = 'multi_applicant_generator:access';

    const EXPIRED_CONTRACTS_READ = 'expired_contracts:read';
    const EXPIRED_MEDICAL_INSURANCE_READ = 'expired_medical_insurance:read';

    const STATUS_HISTORY_READ = 'status_history:read';

    const USERS_READ = 'users:read';
    const USERS_CREATE = 'users:create';
    const USERS_CREATE_COMPANIES = 'users:create_companies';
    const USERS_CREATE_AGENTS = 'users:create_agents';
    const USERS_UPDATE = 'users:update';
    const USERS_DELETE = 'users:delete';
    const USERS_PASSWORD_RESET = 'users:password_reset';

    const JOB_POSTINGS_READ = 'job_postings:read';
    const JOB_POSTINGS_CREATE = 'job_postings:create';
    const JOB_POSTINGS_UPDATE = 'job_postings:update';
    const JOB_POSTINGS_DELETE = 'job_postings:delete';

    const JOB_POSITIONS_READ = 'job_positions:read';
    const JOB_POSITIONS_CREATE = 'job_positions:create';
    const JOB_POSITIONS_UPDATE = 'job_positions:update';
    const JOB_POSITIONS_DELETE = 'job_positions:delete';
    const FINANCE_READ = 'finances:read';
    const FINANCE_CREATE = 'finances:create';
    const FINANCE_UPDATE = 'finances:update';
    const FINANCE_DELETE = 'finances:delete';
    const FINANCE_EXPORT = 'finances:export';

    const INSURANCE_READ = 'insurance:read';
    const INSURANCE_CREATE = 'insurance:create';
    const INSURANCE_UPDATE = 'insurance:update';
    const INSURANCE_DELETE = 'insurance:delete';

    const NOTIFICATIONS_READ = 'notifications:read';
    const NOTIFICATIONS_UPDATE = 'notifications:update';

    const DOCUMENTS_READ = 'documents:read';
    const DOCUMENTS_CREATE = 'documents:create';
    const DOCUMENTS_UPDATE = 'documents:update';
    const DOCUMENTS_DELETE = 'documents:delete';
    const DOCUMENTS_UPLOAD = 'documents:upload';
    const DOCUMENTS_DOWNLOAD = 'documents:download';
    const DOCUMENTS_GENERATE = 'documents:generate';
    const DOCUMENTS_PREPARATION = 'documents:preparation';

    const CHANGE_LOGS_READ = 'change_logs:read';
    const CHANGE_LOGS_CREATE = 'change_logs:create';
    const CHANGE_LOGS_APPROVE = 'change_logs:approve';
    const CHANGE_LOGS_DELETE = 'change_logs:delete';
}
