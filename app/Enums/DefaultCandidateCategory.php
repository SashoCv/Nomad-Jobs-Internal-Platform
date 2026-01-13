<?php

namespace App\Enums;

use App\Models\Role;

enum DefaultCandidateCategory: string
{
    case VISITING = 'За пристигане';
    case VISA = 'За виза';

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
        };
    }
}
