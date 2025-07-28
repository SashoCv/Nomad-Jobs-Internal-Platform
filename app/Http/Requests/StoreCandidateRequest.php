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
            'type_id' => ['required', 'integer', Rule::in([Candidate::TYPE_CANDIDATE, Candidate::TYPE_EMPLOYEE])],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],

            // Personal Information
            'gender' => ['required', 'string', 'max:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'phoneNumber' => ['nullable', 'string', 'max:20'],
            'nationality' => ['required', 'string', 'max:100'],
            'date' => ['required'],
            'birthday' => ['nullable'],
            'address' => ['string', 'max:500'],
            'passport' => ['required', 'string', 'max:50'],
            'fullName' => ['required', 'string', 'max:255'],
            'fullNameCyrillic' => ['nullable', 'string', 'max:255'],
            'placeOfBirth' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'areaOfResidence' => ['nullable', 'string', 'max:100'],
            'addressOfResidence' => ['nullable', 'string', 'max:500'],
            'periodOfResidence' => ['nullable', 'string', 'max:100'],
            'passportValidUntil' => ['required', 'date'],
            'passportIssuedBy' => ['required', 'string', 'max:255'],
            'passportIssuedOn' => ['required', 'date'],
            'addressOfWork' => ['nullable', 'string', 'max:500'],
            'nameOfFacility' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'martialStatus' => ['nullable', 'string', 'max:50'],

            // Contract Information
            'contractPeriod' => ['required', 'string', 'max:100'],
            'contractType' => ['required', 'string', Rule::in([Candidate::CONTRACT_TYPE_90_DAYS, Candidate::CONTRACT_TYPE_YEARLY, Candidate::CONTRACT_TYPE_9_MONTHS])],
            'contractExtensionPeriod' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'workingTime' => ['nullable', 'string', 'max:100'],
            'workingDays' => ['nullable', 'string', 'max:100'],
            'startContractDate' => ['nullable', 'date'],
            'endContractDate' => ['nullable', 'date'],

            // Additional Information
            'dossierNumber' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // File uploads
            'personPassport' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'personPicture' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_id.required' => 'Type is required.',
            'company_id.required' => 'Company is required.',
            'position_id.required' => 'Position is required.',
            'email.email' => 'Please provide a valid email address.',
            'personPassport.max' => 'Passport file must not exceed 10MB.',
            'personPicture.max' => 'Picture file must not exceed 5MB.',
        ];
    }
}
