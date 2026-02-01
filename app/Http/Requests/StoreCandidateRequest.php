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
        ];
    }
}
