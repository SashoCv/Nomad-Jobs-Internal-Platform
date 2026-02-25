<?php

namespace App\Enums;

use App\Models\Role;

enum DefaultCandidateCategory: string
{
    case VISITING = 'За пристигане';
    case VISA = 'За виза';
    case FILES_FROM_AGENT = 'files from agent';

    public function definition(): \App\Values\CategoryDefinition
    {
        return match($this) {
            self::VISITING => new \App\Values\CategoryDefinition(
                name: 'За пристигане',
                description: 'Документи свързани с пристигането и настаняването',
                roleId: Role::AGENT,
            ),
            self::VISA => new \App\Values\CategoryDefinition(
                name: 'За виза',
                description: 'Документи необходими за издаване на виза',
                roleId: Role::AGENT,
            ),
            self::FILES_FROM_AGENT => new \App\Values\CategoryDefinition(
                name: 'files from agent',
                description: 'Файлове от агент',
                roleId: [
                    Role::AGENT,
                    Role::GENERAL_MANAGER,
                    Role::MANAGER,
                    Role::COMPANY_USER,
                    Role::COMPANY_OWNER,
                    Role::OFFICE,
                    Role::HR,
                    Role::OFFICE_MANAGER,
                    Role::RECRUITERS,
                    Role::FINANCE,
                ],
            ),
        };
    }
}
