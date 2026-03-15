<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentCandidateResource extends JsonResource
{
    public function toArray($request)
    {
        $agent = $this->candidate->agent ?? null;
        $contract = $this->contract;

        // Company: prefer companyJob, fall back to contract
        $company = null;
        if ($this->companyJob && $this->companyJob->company) {
            $company = [
                'id' => $this->companyJob->company->id,
                'name' => $this->companyJob->company->nameOfCompany,
            ];
        } elseif ($contract && $contract->company) {
            $company = [
                'id' => $contract->company->id,
                'name' => $contract->company->nameOfCompany,
            ];
        }

        // Job/contract info: prefer companyJob, fall back to contract
        $companyJob = null;
        if ($this->companyJob) {
            $companyJob = [
                'id' => $this->companyJob->id,
                'job_title' => $this->companyJob->job_title,
                'salary' => $this->companyJob->salary,
                'contract_type' => $this->companyJob->contract_type,
            ];
        } elseif ($contract) {
            $companyJob = [
                'id' => null,
                'job_title' => $contract->position?->jobPosition,
                'salary' => $contract->salary,
                'contract_type' => $contract->contract_type,
            ];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nomad_office_id' => $this->nomad_office_id,
            'status_for_candidate_from_agent_id' => $this->status_for_candidate_from_agent_id,
            'created_at' => $this->created_at,
            'company' => $company,
            'company_job' => $companyJob,
            'candidate' => [
                'id' => $this->candidate->id,
                'fullName' => $this->candidate->fullName,
                'fullNameCyrillic' => $this->candidate->fullNameCyrillic,
                'personPicturePath' => $this->candidate->personPicturePath,
                'phoneNumber' => $this->candidate->phoneNumber,
                'nationality' => $this->candidate->nationality,
                'birthday' => $this->candidate->birthday,
            ],
            'status_for_candidate_from_agent' => $this->statusForCandidateFromAgent ? [
                'id' => $this->statusForCandidateFromAgent->id,
                'name' => $this->statusForCandidateFromAgent->name,
                'allow_reassign' => (bool) $this->statusForCandidateFromAgent->allow_reassign,
            ] : null,
            'Agent' => $agent ? [
                'id' => $agent->id,
                'firstName' => $agent->firstName,
                'lastName' => $agent->lastName,
                'email' => $agent->email,
            ] : null,
        ];
    }
}
