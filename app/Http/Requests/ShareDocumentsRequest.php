<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareDocumentsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Use middleware for permission checks
    }

    public function rules()
    {
        return [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'exists:files,id',
            'recipients' => 'required|array|min:1',
            'recipient_type' => 'required|in:internal,external',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ];
    }
}
