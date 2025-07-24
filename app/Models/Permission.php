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

    // Admin Permission constants
    const DASHBOARD_VIEW = 'dashboard.view';
    const HOME_VIEW = 'home.view';
    const HOME_FILTER = 'home.filter';
    const HOME_ARRIVALS = 'home.arrivals';
    const HOME_CHANGE_STATUS = 'home.change_status';

    const COMPANIES_VIEW = 'companies.view';
    const COMPANIES_CREATE = 'companies.create';
    const COMPANIES_EDIT = 'companies.edit';
    const COMPANIES_DELETE = 'companies.delete';
    const COMPANIES_CONTRACTS = 'companies.contracts';

    // Company Contracts - separate permissions
    const COMPANIES_CONTRACTS_VIEW = 'companies.contracts.view';
    const COMPANIES_CONTRACTS_CREATE = 'companies.contracts.create';
    const COMPANIES_CONTRACTS_EDIT = 'companies.contracts.edit';
    const COMPANIES_CONTRACTS_DELETE = 'companies.contracts.delete';

    const INDUSTRIES_VIEW = 'industries.view';
    const INDUSTRIES_CREATE = 'industries.create';
    const INDUSTRIES_EDIT = 'industries.edit';
    const INDUSTRIES_DELETE = 'industries.delete';

    const CONTRACTS_VIEW = 'contracts.view';
    const CONTRACTS_CREATE = 'contracts.create';
    const CONTRACTS_EDIT = 'contracts.edit';
    const CONTRACTS_DELETE = 'contracts.delete';

    const REQUESTS_VIEW = 'requests.view';
    const REQUESTS_APPROVE = 'requests.approve';
    const REQUESTS_DELETE = 'requests.delete';

    const CANDIDATES_VIEW = 'candidates.view';
    const CANDIDATES_CREATE = 'candidates.create';
    const CANDIDATES_EDIT = 'candidates.edit';
    const CANDIDATES_DELETE = 'candidates.delete';
    const CANDIDATES_EXPORT = 'candidates.export';

    const AGENT_CANDIDATES_VIEW = 'agent_candidates.view';
    const AGENT_CANDIDATES_CHANGE_STATUS = 'agent_candidates.change_status';
    const AGENT_CANDIDATES_DELETE = 'agent_candidates.delete';

    const MULTI_APPLICANT_GENERATOR = 'multi_applicant_generator.access';

    const EXPIRED_CONTRACTS_VIEW = 'expired_contracts.view';
    const EXPIRED_MEDICAL_INSURANCE_VIEW = 'expired_medical_insurance.view';

    const STATUS_HISTORY_VIEW = 'status_history.view';

    const USERS_VIEW = 'users.view';
    const USERS_CREATE = 'users.create';
    const USERS_CREATE_COMPANIES = 'users.create.companies';
    const USERS_CREATE_AGENTS = 'users.create.agents';
    const USERS_EDIT = 'users.edit';
    const USERS_DELETE = 'users.delete';
    const USERS_PASSWORD_RESET = 'users.password_reset';

    const JOB_POSTINGS_VIEW = 'job_postings.view';
    const JOB_POSTINGS_CREATE = 'job_postings.create';
    const JOB_POSTINGS_EDIT = 'job_postings.edit';
    const JOB_POSTINGS_DELETE = 'job_postings.delete';

    const JOB_POSITIONS_VIEW = 'job_positions.view';
    const JOB_POSITIONS_CREATE = 'job_positions.create';
    const JOB_POSITIONS_EDIT = 'job_positions.edit';
    const JOB_POSITIONS_DELETE = 'job_positions.delete';

    const JOBS_VIEW = 'jobs.view';
    const JOBS_CREATE = 'jobs.create';
    const JOBS_EDIT = 'jobs.edit';
    const JOBS_DELETE = 'jobs.delete';

    const FINANCES_VIEW = 'finances.view';
    const FINANCES_CREATE = 'finances.create';
    const FINANCES_EDIT = 'finances.edit';
    const FINANCES_DELETE = 'finances.delete';

    const FINANCE_VIEW = 'finance.view';
    const FINANCE_CREATE = 'finance.create';
    const FINANCE_EDIT = 'finance.edit';
    const FINANCE_DELETE = 'finance.delete';
    const FINANCE_EXPORT = 'finance.export';

    const INSURANCE_READ = 'insurance.read';
    const INSURANCE_CREATE = 'insurance.create';
    const INSURANCE_UPDATE = 'insurance.update';
    const INSURANCE_DELETE = 'insurance.delete';

    const NOTIFICATIONS_READ = 'notifications.read';
    const NOTIFICATIONS_UPDATE = 'notifications.update';

    const DOCUMENTS_VIEW = 'documents.view';
    const DOCUMENTS_CREATE = 'documents.create';
    const DOCUMENTS_EDIT = 'documents.edit';
    const DOCUMENTS_DELETE = 'documents.delete';
    const DOCUMENTS_UPLOAD = 'documents.upload';
    const DOCUMENTS_DOWNLOAD = 'documents.download';
    const DOCUMENTS_GENERATE = 'documents.generate';
    const DOCUMENTS_PREPARATION = 'documents.preparation';
}
