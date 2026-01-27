<?php

namespace App\Traits;

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

trait ChecksCandidateDocumentAccess
{
    use HasRolePermissions;

    protected function canAccessCandidateDocument(User $user, ?int $candidateId): bool
    {
        if (!$candidateId) {
            return false;
        }

        if ($this->checkPermission(Permission::DOCUMENTS_DELETE)) {
            return true;
        }

        if ($user->hasRole(Role::AGENT)) {
            $candidate = Candidate::find($candidateId);
            return $candidate && $candidate->agent_id === $user->id;
        }

        return false;
    }
}
