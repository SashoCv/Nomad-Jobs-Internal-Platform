<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class CompanyOwnerPermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::COMPANIES_READ,
            Permission::COMPANIES_CREATE,
            Permission::COMPANIES_UPDATE,
            Permission::COMPANIES_CONTRACTS_READ,
            Permission::COMPANIES_CONTRACTS_CREATE,
            Permission::COMPANIES_CONTRACTS_UPDATE,
            Permission::COMPANIES_CONTRACTS_DELETE,
            Permission::CANDIDATES_READ,
            Permission::CANDIDATES_UPDATE,
            Permission::JOB_POSTINGS_READ,
            Permission::JOB_POSTINGS_CREATE,
            Permission::JOB_POSTINGS_UPDATE,
            Permission::COMPANY_JOB_REQUESTS_READ,
            Permission::CHANGE_LOGS_READ,
        ];
    }
}