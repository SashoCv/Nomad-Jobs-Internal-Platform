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
}
