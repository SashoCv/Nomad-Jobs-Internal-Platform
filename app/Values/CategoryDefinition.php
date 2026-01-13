<?php

namespace App\Values;

class CategoryDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $roleId,
        public readonly int $isGenerated = 0,
    ) {}
}
