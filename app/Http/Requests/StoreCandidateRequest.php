<?php

namespace App\Http\Requests;

use App\Models\Candidate;
use App\Traits\HasRolePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }
}
