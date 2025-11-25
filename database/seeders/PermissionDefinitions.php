<?php

namespace Database\Seeders;

use App\Models\Permission;

class PermissionDefinitions
{
    public static function getAllPermissions(): array
    {
        return [
            // Dashboard
            ['name' => Permission::DASHBOARD_READ, 'slug' => 'dashboard:read', 'description' => 'Read dashboard'],
            ['name' => Permission::COMPANY_DASHBOARD_READ, 'slug' => 'company_dashboard:read', 'description' => 'Access company dashboard view'],

            // Companies
            ['name' => Permission::COMPANIES_READ, 'slug' => 'companies:read', 'description' => 'Read companies'],
            ['name' => Permission::COMPANIES_CREATE, 'slug' => 'companies:create', 'description' => 'Create companies'],
            ['name' => Permission::COMPANIES_UPDATE, 'slug' => 'companies:update', 'description' => 'Edit companies'],
            ['name' => Permission::COMPANIES_DELETE, 'slug' => 'companies:delete', 'description' => 'Delete companies'],
            ['name' => Permission::COMPANIES_CONTRACTS, 'slug' => 'companies:contracts', 'description' => 'Manage company contracts'],

            // Users
            ['name' => Permission::USERS_READ, 'slug' => 'users:read', 'description' => 'Read users'],
            ['name' => Permission::USERS_CREATE, 'slug' => 'users:create', 'description' => 'Create users'],
            ['name' => Permission::USERS_UPDATE, 'slug' => 'users:update', 'description' => 'Edit users'],
            ['name' => Permission::USERS_DELETE, 'slug' => 'users:delete', 'description' => 'Delete users'],
            ['name' => Permission::USERS_CREATE_COMPANIES, 'slug' => 'users:create_companies', 'description' => 'Create company users only'],
            ['name' => Permission::USERS_CREATE_AGENTS, 'slug' => 'users:create_agents', 'description' => 'Create agent users only'],
            ['name' => Permission::USERS_PASSWORD_RESET, 'slug' => 'users:password_reset', 'description' => 'Reset user passwords'],

            // Candidates
            ['name' => Permission::CANDIDATES_READ, 'slug' => 'candidates:read', 'description' => 'Read candidates'],
            ['name' => Permission::CANDIDATES_CREATE, 'slug' => 'candidates:create', 'description' => 'Create candidates'],
            ['name' => Permission::CANDIDATES_UPDATE, 'slug' => 'candidates:update', 'description' => 'Edit candidates'],
            ['name' => Permission::CANDIDATES_DELETE, 'slug' => 'candidates:delete', 'description' => 'Delete candidates'],
            ['name' => Permission::CANDIDATES_EXPORT, 'slug' => 'candidates:export', 'description' => 'Export candidates'],

            // Finance
            ['name' => Permission::FINANCE_READ, 'slug' => 'finances:read', 'description' => 'Read finance'],
            ['name' => Permission::FINANCE_CREATE, 'slug' => 'finances:create', 'description' => 'Create finance records'],
            ['name' => Permission::FINANCE_UPDATE, 'slug' => 'finances:update', 'description' => 'Edit finance records'],
            ['name' => Permission::FINANCE_DELETE, 'slug' => 'finances:delete', 'description' => 'Delete finance records'],
            ['name' => Permission::FINANCE_EXPORT, 'slug' => 'finances:export', 'description' => 'Export finance data'],

            // Insurance
            ['name' => Permission::INSURANCE_READ, 'slug' => 'insurance:read', 'description' => 'Read insurance'],
            ['name' => Permission::INSURANCE_CREATE, 'slug' => 'insurance:create', 'description' => 'Create insurance'],
            ['name' => Permission::INSURANCE_UPDATE, 'slug' => 'insurance:update', 'description' => 'Edit insurance'],
            ['name' => Permission::INSURANCE_DELETE, 'slug' => 'insurance:delete', 'description' => 'Delete insurance'],

            // Documents
            ['name' => Permission::DOCUMENTS_READ, 'slug' => 'documents:read', 'description' => 'Read documents'],
            ['name' => Permission::DOCUMENTS_CREATE, 'slug' => 'documents:create', 'description' => 'Create documents'],
            ['name' => Permission::DOCUMENTS_UPDATE, 'slug' => 'documents:update', 'description' => 'Edit documents'],
            ['name' => Permission::DOCUMENTS_DELETE, 'slug' => 'documents:delete', 'description' => 'Delete documents'],
            ['name' => Permission::DOCUMENTS_UPLOAD, 'slug' => 'documents:upload', 'description' => 'Upload documents'],
            ['name' => Permission::DOCUMENTS_DOWNLOAD, 'slug' => 'documents:download', 'description' => 'Download documents'],
            ['name' => Permission::DOCUMENTS_GENERATE, 'slug' => 'documents:generate', 'description' => 'Generate documents'],
            ['name' => Permission::DOCUMENTS_PREPARATION, 'slug' => 'documents:preparation', 'description' => 'Prepare documents'],

            // Notifications
            ['name' => Permission::NOTIFICATIONS_READ, 'slug' => 'notifications:read', 'description' => 'Read notifications'],
            ['name' => Permission::NOTIFICATIONS_UPDATE, 'slug' => 'notifications:update', 'description' => 'Edit notifications'],

            // Job Postings
            ['name' => Permission::JOB_POSTINGS_READ, 'slug' => 'job_postings:read', 'description' => 'Read job postings'],
            ['name' => Permission::JOB_POSTINGS_CREATE, 'slug' => 'job_postings:create', 'description' => 'Create job postings'],
            ['name' => Permission::JOB_POSTINGS_UPDATE, 'slug' => 'job_postings:update', 'description' => 'Edit job postings'],
            ['name' => Permission::JOB_POSTINGS_DELETE, 'slug' => 'job_postings:delete', 'description' => 'Delete job postings'],

            // Job Positions
            ['name' => Permission::JOB_POSITIONS_READ, 'slug' => 'job_positions:read', 'description' => 'Read job positions'],
            ['name' => Permission::JOB_POSITIONS_CREATE, 'slug' => 'job_positions:create', 'description' => 'Create job positions'],
            ['name' => Permission::JOB_POSITIONS_UPDATE, 'slug' => 'job_positions:update', 'description' => 'Edit job positions'],
            ['name' => Permission::JOB_POSITIONS_DELETE, 'slug' => 'job_positions:delete', 'description' => 'Delete job positions'],

            // Industries
            ['name' => Permission::INDUSTRIES_READ, 'slug' => 'industries:read', 'description' => 'Read industries'],
            ['name' => Permission::INDUSTRIES_CREATE, 'slug' => 'industries:create', 'description' => 'Create industries'],
            ['name' => Permission::INDUSTRIES_UPDATE, 'slug' => 'industries:update', 'description' => 'Edit industries'],
            ['name' => Permission::INDUSTRIES_DELETE, 'slug' => 'industries:delete', 'description' => 'Delete industries'],

            // Company Contracts
            ['name' => Permission::COMPANIES_CONTRACTS_READ, 'slug' => 'companies_contracts:read', 'description' => 'Read company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_CREATE, 'slug' => 'companies_contracts:create', 'description' => 'Create company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_UPDATE, 'slug' => 'companies_contracts:update', 'description' => 'Edit company contracts'],
            ['name' => Permission::COMPANIES_CONTRACTS_DELETE, 'slug' => 'companies_contracts:delete', 'description' => 'Delete company contracts'],

            // Home
            ['name' => Permission::HOME_READ, 'slug' => 'home:read', 'description' => 'Access home page'],
            ['name' => Permission::HOME_FILTER, 'slug' => 'home:filter', 'description' => 'Filter home page data'],
            ['name' => Permission::HOME_ARRIVALS, 'slug' => 'home:arrivals', 'description' => 'Access arrivals on home page'],
            ['name' => Permission::HOME_CHANGE_STATUS, 'slug' => 'home:change_status', 'description' => 'Change status on home page'],
            ['name' => Permission::APPROVED_CANDIDATES_READ, 'slug' => 'approved_candidates:read', 'description' => 'Access approved candidates page'],
            ['name' => Permission::HR_REPORTS_READ, 'slug' => 'hr_reports:read', 'description' => 'Access HR reports page'],

            // Expired Items
            ['name' => Permission::EXPIRED_CONTRACTS_READ, 'slug' => 'expired_contracts:read', 'description' => 'Read expired contracts'],
            ['name' => Permission::EXPIRED_MEDICAL_INSURANCE_READ, 'slug' => 'expired_medical_insurance:read', 'description' => 'Read expired medical insurance'],

            // Multi Applicant Generator
            ['name' => Permission::MULTI_APPLICANT_GENERATOR, 'slug' => 'multi_applicant_generator:access', 'description' => 'Access multi applicant generator'],

            // Status History
            ['name' => Permission::STATUS_HISTORY_READ, 'slug' => 'status_history:read', 'description' => 'Read status history'],

            // Candidates from Agent (Admin-only views)
            ['name' => Permission::CANDIDATES_FROM_AGENT_READ, 'slug' => 'candidates_from_agent:read', 'description' => 'Read candidates from agent'],
            ['name' => Permission::CANDIDATES_FROM_AGENT_CREATE, 'slug' => 'candidates_from_agent:create', 'description' => 'Create candidates from agent'],
            ['name' => Permission::CANDIDATES_FROM_AGENT_CHANGE_STATUS, 'slug' => 'candidates_from_agent:change_status', 'description' => 'Change status of candidates from agent'],
            ['name' => Permission::CANDIDATES_FROM_AGENT_DELETE, 'slug' => 'candidates_from_agent:delete', 'description' => 'Delete candidates from agent'],

            // Company Job Requests
            ['name' => Permission::COMPANY_JOB_REQUESTS_READ, 'slug' => 'requests:read', 'description' => 'Read company job requests'],
            ['name' => Permission::COMPANY_JOB_REQUESTS_APPROVE, 'slug' => 'requests:approve', 'description' => 'Approve company job requests'],
            ['name' => Permission::COMPANY_JOB_REQUESTS_DELETE, 'slug' => 'requests:delete', 'description' => 'Delete company job requests'],

            // Change Logs
            ['name' => Permission::CHANGE_LOGS_READ, 'slug' => 'change_logs:read', 'description' => 'Read change logs'],
            ['name' => Permission::CHANGE_LOGS_CREATE, 'slug' => 'change_logs:create', 'description' => 'Create change logs'],
            ['name' => Permission::CHANGE_LOGS_APPROVE, 'slug' => 'change_logs:approve', 'description' => 'Approve change logs'],
            ['name' => Permission::CHANGE_LOGS_DELETE, 'slug' => 'change_logs:delete', 'description' => 'Delete change logs'],

            // Agent Permissions (Agent-specific)
            ['name' => Permission::AGENT_CANDIDATES_CREATE, 'slug' => 'agent:candidates:create', 'description' => 'Create agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_READ, 'slug' => 'agent:candidates:read', 'description' => 'Read agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_UPDATE, 'slug' => 'agent:candidates:update', 'description' => 'Update agent candidates'],
            ['name' => Permission::AGENT_CANDIDATES_DELETE, 'slug' => 'agent:candidates:delete', 'description' => 'Delete agent candidates'],
            ['name' => Permission::AGENT_COMPANIES_READ, 'slug' => 'agent:companies:read', 'description' => 'View agent companies'],

            // Transport
            ['name' => Permission::TRANSPORT_READ, 'slug' => 'transport:read', 'description' => 'Read transport'],
            ['name' => Permission::TRANSPORT_CREATE, 'slug' => 'transport:create', 'description' => 'Create transport'],
            ['name' => Permission::TRANSPORT_COVERAGE, 'slug' => 'transport:coverage', 'description' => 'Manage transport coverage']

        ];
    }
}
