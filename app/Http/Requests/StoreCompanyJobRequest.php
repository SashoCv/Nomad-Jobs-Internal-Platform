<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCompanyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required_if:role_id,1,2,5|exists:companies,id',
            'job_title' => 'nullable|string|max:255',
            'position_id' => 'required|integer|exists:positions,id',
            'number_of_positions' => 'required|integer|min:1',
            'job_description' => 'nullable|string',
            'contract_type' => 'nullable|string|max:255',
            'requirementsForCandidates' => 'nullable|string',
            'salary' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'workTime' => 'nullable|string|max:255',
            'additionalWork' => 'nullable|string',
            'vacationDays' => 'nullable|integer|min:0',
            'rent' => 'nullable|numeric|min:0',
            'food' => 'nullable|numeric|min:0',
            'otherDescription' => 'nullable|string',
            'agentsIds' => 'nullable|array',
            'agentsIds.*' => 'exists:users,id',

        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required_if' => 'Company is required for this role.',
            'company_id.exists' => 'Selected company does not exist.',
            'position_id.required' => 'Изберете длъжност е задължително.',
            'position_id.exists' => 'Избраната длъжност не съществува.',
            'job_title.max' => 'Длъжността не може да надвишава 255 символа.',
            'number_of_positions.required' => 'Number of positions is required.',
            'number_of_positions.integer' => 'Number of positions must be an integer.',
            'number_of_positions.min' => 'Number of positions must be at least 1.',
            'salary.numeric' => 'Salary must be a number.',
            'salary.min' => 'Salary cannot be negative.',
            'bonus.numeric' => 'Bonus must be a number.',
            'bonus.min' => 'Bonus cannot be negative.',
            'vacationDays.integer' => 'Vacation days must be an integer.',
            'vacationDays.min' => 'Vacation days cannot be negative.',
            'rent.numeric' => 'Rent must be a number.',
            'rent.min' => 'Rent cannot be negative.',
            'food.numeric' => 'Food allowance must be a number.',
            'food.min' => 'Food allowance cannot be negative.',
            'agentsIds.array' => 'Agents must be an array.',
            'agentsIds.*.exists' => 'Selected agent does not exist.',
        ];
    }
}
