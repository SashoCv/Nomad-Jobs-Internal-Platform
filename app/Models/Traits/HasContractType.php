<?php

namespace App\Models\Traits;

use App\Models\ContractType;

/**
 * Trait for models that have contract_type (slug) and contract_type_id (FK) fields.
 * Automatically syncs contract_type_id when contract_type slug is set.
 *
 * Requirements:
 * - Model must have 'contract_type' column (string slug)
 * - Model must have 'contract_type_id' column (FK to contract_types)
 */
trait HasContractType
{
    /**
     * Mutator: When contract_type (slug) is set, automatically resolve contract_type_id.
     * This ensures both fields stay in sync - single source of truth pattern.
     */
    public function setContractTypeAttribute(?string $value): void
    {
        // Determine the correct attribute name (some models use camelCase)
        $slugAttribute = $this->getContractTypeSlugAttribute();

        $this->attributes[$slugAttribute] = $value;
        $this->attributes['contract_type_id'] = $value
            ? ContractType::where('slug', $value)->value('id')
            : null;
    }

    /**
     * Get the attribute name for the contract type slug.
     * Override in model if using different column name (e.g., 'contractType').
     */
    protected function getContractTypeSlugAttribute(): string
    {
        return 'contract_type';
    }
}
