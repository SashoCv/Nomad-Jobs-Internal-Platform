<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class AgentPermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::JOB_POSTINGS_READ,
            Permission::AGENT_CANDIDATES_READ,
            Permission::AGENT_CANDIDATES_CREATE,
            Permission::AGENT_CANDIDATES_UPDATE,
            Permission::AGENT_CANDIDATES_DELETE,
            Permission::DOCUMENTS_READ,
            Permission::DOCUMENTS_CREATE,
            Permission::DOCUMENTS_UPLOAD,
            Permission::DOCUMENTS_DELETE,
            Permission::DOCUMENTS_CATEGORIES_READ,
            Permission::DOCUMENTS_CATEGORIES_CREATE,
            Permission::DOCUMENTS_CATEGORIES_UPDATE,
            Permission::DOCUMENTS_CATEGORIES_DELETE,
            Permission::AGENT_COMPANIES_READ,
            Permission::AGENTS_CONTRACTS_READ,
            Permission::COMPANIES_READ,
            Permission::CITIES_READ,
        ];
    }
}
