<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShareDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fileType = $this->input('file_type', 'candidate');
        $table = $fileType === 'company' ? 'company_files' : 'files';

        return [
            'file_ids' => 'sometimes|array',
            'file_ids.*' => "exists:{$table},id",
            'file_type' => 'sometimes|in:candidate,company',
            'passport_id' => 'nullable|integer|exists:candidate_passports,id',
            'visa_id' => 'nullable|integer|exists:candidate_visas,id',
            'recipients' => 'required|array|min:1',
            'recipient_type' => 'required|in:internal,external',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fileIds = $this->input('file_ids', []);

            if (!empty($fileIds) || $this->input('passport_id') || $this->input('visa_id')) {
                return;
            }

            $validator->errors()->add('file_ids', 'At least one file, passport, or visa must be selected.');
        });
    }
}
