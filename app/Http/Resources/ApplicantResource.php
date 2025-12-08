<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantResource extends JsonResource
{
    /**
     * Transform the applicant resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'fullNameCyrillic' => $this->fullNameCyrillic,
            'contractType' => $this->contractType,
            'personPicturePath' => $this->personPicturePath,
            'personPictureName' => $this->personPictureName,
            'country' => $this->country,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // Relationships
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'nameOfCompany' => $this->company->nameOfCompany,
                ];
            }),
            'position' => $this->whenLoaded('agentCandidates', function () {
                $agentCandidate = $this->agentCandidates->first();
                if ($agentCandidate && $agentCandidate->companyJob) {
                    return [
                        'id' => $agentCandidate->companyJob->id,
                        'jobPosition' => $agentCandidate->companyJob->job_title,
                    ];
                }
                return null;
            }),
            'agentStatus' => $this->whenLoaded('agentCandidates', function () {
                $agentCandidate = $this->agentCandidates->first();
                if ($agentCandidate && $agentCandidate->statusForCandidateFromAgent) {
                    return [
                        'id' => $agentCandidate->statusForCandidateFromAgent->id,
                        'name' => $agentCandidate->statusForCandidateFromAgent->name,
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
