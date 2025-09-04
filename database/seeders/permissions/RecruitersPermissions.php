<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class RecruitersPermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::DASHBOARD_READ,
            Permission::COMPANIES_READ,
            Permission::CANDIDATES_READ,
            Permission::CANDIDATES_CREATE,
            Permission::CANDIDATES_UPDATE,
            Permission::CANDIDATES_DELETE,
            Permission::CANDIDATES_EXPORT,
            Permission::CANDIDATES_FROM_AGENT_READ,
            Permission::CANDIDATES_FROM_AGENT_CHANGE_STATUS,
            Permission::CANDIDATES_FROM_AGENT_DELETE,
            Permission::MULTI_APPLICANT_GENERATOR,
            Permission::EXPIRED_CONTRACTS_READ,
            Permission::EXPIRED_MEDICAL_INSURANCE_READ,
            Permission::STATUS_HISTORY_READ,
            Permission::USERS_READ,
            Permission::USERS_CREATE,
            Permission::USERS_CREATE_COMPANIES,
            Permission::USERS_CREATE_AGENTS,
            Permission::USERS_PASSWORD_RESET,
            Permission::JOB_POSTINGS_READ,
            Permission::JOB_POSTINGS_CREATE,
            Permission::JOB_POSTINGS_UPDATE,
            Permission::JOB_POSTINGS_DELETE,
            Permission::JOB_POSITIONS_READ,
            Permission::JOB_POSITIONS_CREATE,
            Permission::JOB_POSITIONS_UPDATE,
            Permission::JOB_POSITIONS_DELETE,
            Permission::INSURANCE_READ,
            Permission::INSURANCE_CREATE,
            Permission::INSURANCE_UPDATE,
            Permission::INSURANCE_DELETE,
            Permission::NOTIFICATIONS_READ,
            Permission::NOTIFICATIONS_UPDATE,
            Permission::DOCUMENTS_READ,
            Permission::DOCUMENTS_CREATE,
            Permission::DOCUMENTS_UPDATE,
            Permission::DOCUMENTS_DELETE,
            Permission::DOCUMENTS_UPLOAD,
            Permission::DOCUMENTS_DOWNLOAD,
            Permission::DOCUMENTS_GENERATE,
            Permission::DOCUMENTS_PREPARATION,
            Permission::CHANGE_LOGS_READ,
            Permission::CHANGE_LOGS_CREATE,
            Permission::HOME_READ,
            Permission::HOME_FILTER,
            Permission::HOME_ARRIVALS,
            Permission::HOME_CHANGE_STATUS,
        ];
    }
}