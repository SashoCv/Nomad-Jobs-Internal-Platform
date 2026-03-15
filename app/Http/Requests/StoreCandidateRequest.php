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

    public function rules(): array
    {
        return [
            'personPassport' => 'required|file|max:10240',
            'passport' => 'required|string|max:50',
            'passportValidUntil' => 'required|date',
            'passportIssuedOn' => 'required|date',
            'passportIssuedBy' => 'required|string|max:255',
            'candidate_source' => 'required|in:agent,direct_employer,assistance_only',
            'agent_id' => 'required_if:candidate_source,agent|nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'personPassport.required' => 'Passport file is required.',
            'personPassport.file' => 'Passport must be a valid file.',
            'personPassport.mimes' => 'Passport must be a JPG, PNG, PDF, DOC, or DOCX file.',
            'personPassport.max' => 'Passport file must not exceed 10MB.',
            'passport.required' => 'Passport number is required.',
            'passportValidUntil.required' => 'Passport expiry date is required.',
            'passportIssuedOn.required' => 'Passport issue date is required.',
            'passportIssuedBy.required' => 'Passport issuing authority is required.',
            'candidate_source.required' => 'Източникът на кандидат е задължителен.',
            'candidate_source.in' => 'Източникът на кандидат трябва да бъде агент, директен работодател или само съдействие.',
            'agent_id.required_if' => 'Агентът е задължителен когато източникът е агент.',
            'agent_id.exists' => 'Избраният агент не съществува.',
        ];
    }
}
