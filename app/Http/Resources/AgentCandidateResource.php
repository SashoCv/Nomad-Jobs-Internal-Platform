<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentCandidateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status_for_candidate_from_agent_id' => $this->status_for_candidate_from_agent_id,
            'company' => $this->companyJob && $this->companyJob->company ? [
                'id' => $this->companyJob->company->id,
                'name' => $this->companyJob->company->nameOfCompany,
            ] : null,  // Return null if companyJob or company is not available
            'company_job' => $this->companyJob ? [
                'id' => $this->companyJob->id,
                'job_title' => $this->companyJob->job_title,
                'salary' => $this->companyJob->salary,
                'contract_type' => $this->companyJob->contract_type,
            ] : null,  // Return null if companyJob is not available
            'candidate' => [
                'id' => $this->candidate->id,
                'fullName' => $this->candidate->fullName,
                'phoneNumber' => $this->candidate->phoneNumber,
                'nationality' => $this->candidate->nationality,
                'birthday' => $this->candidate->birthday,
            ],
            'status_for_candidate_from_agent' => [
                'id' => $this->statusForCandidateFromAgent->id,
                'name' => $this->statusForCandidateFromAgent->name,
            ],
            'Agent' => [
                'id' => $this->user->id,
                'firstName' => $this->user->firstName,
                'lastName' => $this->user->lastName,
                'email' => $this->user->email,
            ],
        ];
    }
}
