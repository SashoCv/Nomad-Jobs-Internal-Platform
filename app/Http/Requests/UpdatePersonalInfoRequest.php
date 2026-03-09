<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePersonalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // Profile picture (optional)
            'personPicture' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',

            // Personal Information
            'fullName' => 'required|string|max:255',
            'fullNameCyrillic' => 'required|string|max:255',
            'birthday' => 'required|date',
            'placeOfBirth' => 'required|string|max:255',
            'nationality' => 'required|string|max:100',
            'country_id' => 'required|integer|exists:countries,id',
            'gender' => 'required|in:male,female',
            'martialStatus' => 'required|string|max:50',

            // Note: Passport data is managed via dedicated /candidate-passports endpoints

            // Residence Information
            'addressOfResidence' => 'nullable|string|max:500',
            'periodOfResidence' => 'nullable|string|max:100',

            // Contact Information
            'phoneNumber' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:255',
            'areaOfResidence' => 'nullable|string|max:255',

            // Education / Skills
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'english_level' => 'nullable|string|max:50',
            'russian_level' => 'nullable|string|max:50',
            'other_language' => 'nullable|string|max:100',
            'other_language_level' => 'nullable|string|max:50',

            // Physical / Health
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'chronic_diseases' => 'nullable|string|max:500',

            // Legal / Driving
            'country_of_visa_application' => 'nullable|string|max:100',
            'has_driving_license' => 'nullable',
            'driving_license_category' => 'nullable|string|max:50',
            'driving_license_expiry' => 'nullable|date',
            'driving_license_country' => 'nullable|string|max:100',

            // Other
            'children_info' => 'nullable|string|max:500',
            'is_qualified' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            // File uploads
            'personPicture.file' => 'Снимката трябва да бъде валиден файл.',
            'personPicture.mimes' => 'Снимката трябва да бъде JPG или PNG файл.',
            'personPicture.max' => 'Снимката не трябва да надвишава 5MB.',

            // Personal Information
            'fullName.required' => 'Името на латиница е задължително.',
            'fullNameCyrillic.required' => 'Името на кирилица е задължително.',
            'birthday.required' => 'Датата на раждане е задължителна.',
            'placeOfBirth.required' => 'Мястото на раждане е задължително.',
            'nationality.required' => 'Националността е задължителна.',
            'country_id.required' => 'Държавата е задължителна.',
            'country_id.exists' => 'Избраната държава не съществува.',
            'gender.required' => 'Полът е задължителен.',
            'gender.in' => 'Полът трябва да бъде мъж или жена.',
            'martialStatus.required' => 'Семейното положение е задължително.',

            // Optional fields
            'email.email' => 'Имейлът трябва да бъде валиден имейл адрес.',
        ];
    }
}
