<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentCandidateResource extends JsonResource
{
    public function toArray($request)
    {
        $addedById = $this->candidate->addedBy;
        $agent = User::where('id', $addedById)->first();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nomad_office_id' => $this->nomad_office_id,
            'status_for_candidate_from_agent_id' => $this->status_for_candidate_from_agent_id,
            'created_at' => $this->created_at,
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
                'fullNameCyrillic' => $this->candidate->fullNameCyrillic,
                'personPicturePath' => $this->candidate->personPicturePath,
                'phoneNumber' => $this->candidate->phoneNumber,
                'nationality' => $this->candidate->nationality,
                'birthday' => $this->candidate->birthday,
            ],
            'status_for_candidate_from_agent' => [
                'id' => $this->statusForCandidateFromAgent->id,
                'name' => $this->statusForCandidateFromAgent->name,
            ],
            'Agent' => [
                'id' => $agent->id,
                'firstName' => $agent->firstName,
                'lastName' => $agent->lastName,
                'email' => $agent->email,
            ],
        ];
    }
}
