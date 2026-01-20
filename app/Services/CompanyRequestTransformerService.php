<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CompanyRequestTransformerService
{
    public function transformCompanyRequests(Collection $companyRequests): array
    {
        return $companyRequests->map(function ($request) {
            return $this->transformSingleRequest($request);
        })->values()->toArray();
    }

    public function transformSingleRequest($request): array
    {
        $companyJob = $request->companyJob;
        $company = $companyJob->company;
        $user = $companyJob->user;

        return [
            'id' => $request->id,
            'approved' => $request->approved,
            'created_at' => $request->created_at,
            'job' => [
                'id' => $companyJob->id,
                'title' => $companyJob->job_title,
                'description' => $companyJob->job_description,
                'positions' => $companyJob->number_of_positions,
                'contract_type' => $companyJob->contract_type,
                'requirements' => $companyJob->requirementsForCandidates,
                'salary' => $companyJob->salary,
                'bonus' => $companyJob->bonus,
                'work_time' => $companyJob->workTime,
                'additional_work' => $companyJob->additionalWork,
                'vacation_days' => $companyJob->vacationDays,
                'rent' => $companyJob->rent,
                'food' => $companyJob->food,
                'other_description' => $companyJob->otherDescription,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->nameOfCompany,
                'email' => $company->default_email,
                'address' => $company->address,
                'city' => $company->companyCity,
                'eik' => $company->EIK,
            ],
            'created_by' => [
                'id' => $user->id,
                'full_name' => $user->firstName . ' ' . $user->lastName,
                'email' => $user->email,
            ],
            'change_logs' => $companyJob->changeLogs->map(function ($changeLog) {
                return [
                    'id' => $changeLog->id,
                    'field_name' => $changeLog->fieldName,
                    'full_name' => $changeLog->user ? ($changeLog->user->firstName . ' ' . $changeLog->user->lastName) : 'Unknown User',
                    'old_value' => $changeLog->oldValue,
                    'new_value' => $changeLog->newValue,
                    'status' => $changeLog->status,
                    'is_applied' => $changeLog->isApplied,
                    'created_at' => $changeLog->created_at,
                ];
            })->values()->toArray()
        ];
    }
}
