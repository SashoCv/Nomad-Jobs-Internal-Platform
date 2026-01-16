<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class CompanyUserPermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::COMPANY_DASHBOARD_READ,
            Permission::COMPANIES_READ,
            Permission::COMPANIES_CONTRACTS_READ,
            Permission::COMPANIES_UPDATE,
            Permission::CANDIDATES_READ,
            Permission::JOB_POSTINGS_READ,
            Permission::JOB_POSTINGS_CREATE,
            Permission::JOB_POSTINGS_UPDATE,
            Permission::COMPANY_JOB_REQUESTS_READ,
            Permission::CHANGE_LOGS_READ,
            Permission::DOCUMENTS_READ,
            Permission::DOCUMENTS_CREATE,
            Permission::DOCUMENTS_UPLOAD,
            Permission::DOCUMENTS_DOWNLOAD,
            Permission::DOCUMENTS_CATEGORIES_READ,
            Permission::DOCUMENTS_CATEGORIES_CREATE,
            Permission::DOCUMENTS_CATEGORIES_UPDATE,
            Permission::DOCUMENTS_CATEGORIES_DELETE,
            Permission::CITIES_READ,
        ];
    }
}