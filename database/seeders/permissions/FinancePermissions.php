<?php

namespace Database\Seeders\Permissions;

use App\Models\Permission;

class FinancePermissions
{
    public static function getPermissions(): array
    {
        return [
            Permission::CANDIDATES_READ,
            Permission::FINANCE_READ,
            Permission::FINANCE_CREATE,
            Permission::FINANCE_UPDATE,
            Permission::FINANCE_DELETE,
            Permission::FINANCE_EXPORT,
        ];
    }
}