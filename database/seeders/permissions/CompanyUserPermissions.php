<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class CompanyUserPermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::COMPANIES_READ,
            Permission::COMPANIES_CONTRACTS_READ,
            Permission::COMPANIES_UPDATE,
            Permission::CANDIDATES_READ,
            Permission::JOB_POSTINGS_READ,
            Permission::JOB_POSTINGS_CREATE,
            Permission::COMPANY_JOB_REQUESTS_READ,
            Permission::CHANGE_LOGS_READ,
        ];
    }
}