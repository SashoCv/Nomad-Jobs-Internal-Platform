<?php

namespace App\Values;

class CategoryDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int|array $roleId,
        public readonly int $isGenerated = 0,
    ) {}

    public function roleIds(): array
    {
        return is_array($this->roleId) ? $this->roleId : [$this->roleId];
    }
}
