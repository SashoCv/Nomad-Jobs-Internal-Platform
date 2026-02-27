<?php

namespace App\Http\Requests;

use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // File uploads (optional for updates)
            'personPassport' => 'nullable|file|max:10240',
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

            // Passport Information (nullable - passport data stored separately in candidate_passports)
            'passport' => 'nullable|string|max:50',
            'passportValidUntil' => 'nullable|date',
            'passportIssuedOn' => 'nullable|date',
            'passportIssuedBy' => 'nullable|string|max:255',

            // Residence Information
            'addressOfResidence' => 'nullable|string|max:500',
            'periodOfResidence' => 'nullable|string|max:100',

            // Contract Information
            'company_id' => 'required|integer|exists:companies,id',
            'position_id' => 'required|integer|exists:positions,id',
            'contractType' => 'required|string|max:50',
            'salary' => 'required|numeric|min:0',
            'workingTime' => 'required|integer|min:1|max:24',
            'workingDays' => 'nullable|integer|min:1|max:7',
            'addressOfWork' => 'required|string|max:500',
            'nameOfFacility' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',

            // Contract dates
            'startContractDate' => 'nullable|date',
            'endContractDate' => 'nullable|date|after_or_equal:startContractDate',
            // Optional fields
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'phoneNumber' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
            'agent_id' => 'nullable|integer|exists:users,id',
            'candidate_source' => 'nullable|in:agent,direct_employer,assistance_only',
            'case_id' => 'nullable|integer|exists:cases,id',
        ];
    }

    public function messages(): array
    {
        return [
            // File uploads
            'personPassport.file' => 'Паспортът трябва да бъде валиден файл.',
            'personPassport.mimes' => 'Паспортът трябва да бъде JPG, PNG, PDF, DOC или DOCX файл.',
            'personPassport.max' => 'Файлът на паспорта не трябва да надвишава 10MB.',
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

            // Passport Information
            'passport.required' => 'Номерът на паспорта е задължителен.',
            'passportValidUntil.required' => 'Датата на валидност на паспорта е задължителна.',
            'passportIssuedOn.required' => 'Датата на издаване на паспорта е задължителна.',
            'passportIssuedBy.required' => 'Органът, издал паспорта е задължителен.',

            // Contract Information
            'company_id.required' => 'Компанията е задължителна.',
            'company_id.exists' => 'Избраната компания не съществува.',
            'position_id.required' => 'Позицията е задължителна.',
            'position_id.exists' => 'Избраната позиция не съществува.',
            'contractType.required' => 'Типът на договора е задължителен.',
            'salary.required' => 'Заплатата е задължителна.',
            'salary.numeric' => 'Заплатата трябва да бъде число.',
            'salary.min' => 'Заплатата не може да бъде отрицателна.',
            'workingTime.required' => 'Работното време е задължително.',
            'workingTime.integer' => 'Работното време трябва да бъде цяло число.',
            'workingTime.min' => 'Работното време трябва да бъде поне 1 час.',
            'workingTime.max' => 'Работното време не може да надвишава 24 часа.',
            'addressOfWork.required' => 'Адресът на работа е задължителен.',
            'user_id.required' => 'Упълномощеният представител е задължителен.',
            'user_id.exists' => 'Избраният упълномощен представител не съществува.',

            // Contract dates
            'endContractDate.after_or_equal' => 'Крайната дата на договора трябва да бъде след началната дата.',

            // Optional fields
            'email.email' => 'Имейлът трябва да бъде валиден имейл адрес.',
            'agent_id.exists' => 'Избраният агент не съществува.',
            'case_id.exists' => 'Избраното дело не съществува.',
        ];
    }
}
