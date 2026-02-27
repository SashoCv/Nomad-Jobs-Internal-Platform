<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAgentCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // Required fields
            'company_job_id' => 'required|integer|exists:company_jobs,id',
            'fullName' => 'required|string|max:255',
            'fullNameCyrillic' => 'required|string|max:255',
            'birthday' => 'required|date',
            'placeOfBirth' => 'required|string|max:255',
            'nationality' => 'required|string|max:100',
            'country_id' => 'required|integer|exists:countries,id',
            'gender' => 'required|in:male,female',

            // Passport Information (required)
            'passport' => 'required|string|max:50',
            'passportValidUntil' => 'required|date',
            'passportIssuedOn' => 'required|date',
            'passportIssuedBy' => 'required|string|max:255',
            'personPassport' => 'nullable|file|max:10240',

            // Optional fields
            'email' => 'nullable|email|max:255',
            'phoneNumber' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:255',
            'areaOfResidence' => 'nullable|string|max:255',
            'addressOfResidence' => 'nullable|string|max:500',
            'periodOfResidence' => 'nullable|string|max:100',
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'martialStatus' => 'nullable|string|max:50',

            // Contract fields
            'contractType' => 'nullable|string|max:50',
            'salary' => 'nullable|numeric|min:0',
            'workingTime' => 'nullable|integer|min:1|max:24',
            'workingDays' => 'nullable|integer|min:1|max:7',
            'addressOfWork' => 'nullable|string|max:500',
            'nameOfFacility' => 'nullable|string|max:255',
            'dossierNumber' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'date' => 'nullable|date',
            'position_id' => 'nullable|integer|exists:positions,id',
            'user_id' => 'nullable|integer|exists:users,id',

            // CV fields
            'personPicture' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'chronic_diseases' => 'nullable|string|max:500',
            'country_of_visa_application' => 'nullable|string|max:100',
            'has_driving_license' => 'nullable',
            'driving_license_category' => 'nullable|string|max:50',
            'driving_license_expiry' => 'nullable|date',
            'driving_license_country' => 'nullable|string|max:100',
            'english_level' => 'nullable|string|max:50',
            'russian_level' => 'nullable|string|max:50',
            'other_language' => 'nullable|string|max:100',
            'other_language_level' => 'nullable|string|max:50',
            'children_info' => 'nullable|string|max:500',

            // Arrays
            'educations' => 'nullable|array',
            'experiences' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'company_job_id.required' => 'Job posting is required.',
            'company_job_id.exists' => 'Selected job posting does not exist.',
            'fullName.required' => 'Full name (Latin) is required.',
            'fullNameCyrillic.required' => 'Full name (Cyrillic) is required.',
            'birthday.required' => 'Date of birth is required.',
            'placeOfBirth.required' => 'Place of birth is required.',
            'nationality.required' => 'Nationality is required.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'Selected country does not exist.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be male or female.',

            // Passport messages
            'passport.required' => 'Passport number is required.',
            'passportValidUntil.required' => 'Passport expiry date is required.',
            'passportIssuedOn.required' => 'Passport issue date is required.',
            'passportIssuedBy.required' => 'Passport issuing authority is required.',
            'personPassport.file' => 'Passport must be a valid file.',
            'personPassport.mimes' => 'Passport must be a JPG, PNG, PDF, DOC, or DOCX file.',
            'personPassport.max' => 'Passport file must not exceed 10MB.',
        ];
    }
}
