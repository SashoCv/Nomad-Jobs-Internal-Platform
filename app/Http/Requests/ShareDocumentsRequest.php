<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareDocumentsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Use middleware for permission checks
    }

    public function rules()
    {
        $fileType = $this->input('file_type', 'candidate');
        $table = $fileType === 'company' ? 'company_files' : 'files';

        return [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => "exists:{$table},id",
            'file_type' => 'sometimes|in:candidate,company',
            'recipients' => 'required|array|min:1',
            'recipient_type' => 'required|in:internal,external',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ];
    }
}
