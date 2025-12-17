<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ApplicantResource extends JsonResource
{
    /**
     * Transform the applicant resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        Log::info('ApplicantResource - Start', [
            'candidate_id' => $this->id,
            'has_country_relation' => isset($this->country),
            'country_id' => $this->country_id,
        ]);

        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'fullNameCyrillic' => $this->fullNameCyrillic,
            'contractType' => $this->contractType,
            'personPicturePath' => $this->personPicturePath,
            'personPictureName' => $this->personPictureName,
            'country_id' => $this->country_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // Relationships
            'country' => $this->whenLoaded('country', function () {
                Log::info('ApplicantResource - Country loaded', [
                    'candidate_id' => $this->id,
                    'country' => $this->country,
                    'country_name' => $this->country?->name,
                ]);
                return $this->country?->name;
            }),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'nameOfCompany' => $this->company->nameOfCompany,
                ];
            }),
            'position' => $this->whenLoaded('agentCandidates', function () {
                Log::info('ApplicantResource - Processing position', ['candidate_id' => $this->id]);
                $agentCandidate = $this->agentCandidates->first();
                Log::info('ApplicantResource - AgentCandidate', [
                    'candidate_id' => $this->id,
                    'has_agent_candidate' => !is_null($agentCandidate),
                    'has_company_job' => $agentCandidate ? !is_null($agentCandidate->companyJob) : false,
                ]);
                if ($agentCandidate && $agentCandidate->companyJob) {
                    return [
                        'id' => $agentCandidate->companyJob->id,
                        'jobPosition' => $agentCandidate->companyJob->job_title,
                    ];
                }
                return null;
            }),
            'agentStatus' => $this->whenLoaded('agentCandidates', function () {
                Log::info('ApplicantResource - Processing agentStatus', ['candidate_id' => $this->id]);
                $agentCandidate = $this->agentCandidates->first();
                Log::info('ApplicantResource - AgentStatus check', [
                    'candidate_id' => $this->id,
                    'has_agent_candidate' => !is_null($agentCandidate),
                    'has_status' => $agentCandidate ? !is_null($agentCandidate->statusForCandidateFromAgent) : false,
                    'status_data' => $agentCandidate && $agentCandidate->statusForCandidateFromAgent ? $agentCandidate->statusForCandidateFromAgent : null,
                ]);
                if ($agentCandidate && $agentCandidate->statusForCandidateFromAgent) {
                    return [
                        'id' => $agentCandidate->statusForCandidateFromAgent->id,
                        'name' => $agentCandidate->statusForCandidateFromAgent->name ?? "",
                    ];
                }
                return null;
            }),
            'categories' => $this->whenLoaded('categories'),

            // Additional fields needed for frontend
            'company_id' => $this->company_id,
        ];
    }
}
