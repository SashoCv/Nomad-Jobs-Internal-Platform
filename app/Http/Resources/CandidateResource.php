<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'fullNameCyrillic' => $this->fullNameCyrillic,
            'fullDisplayName' => $this->full_display_name,
            'email' => $this->when($this->shouldShowPersonalInfo(), $this->email),
            'phoneNumber' => $this->when($this->shouldShowPersonalInfo(), $this->phoneNumber),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'passport' => $this->passport,
            'address' => $this->address,
            'birthday' => $this->birthday,
            'placeOfBirth' => $this->placeOfBirth,
            'country' => $this->country,
            'area' => $this->area,
            'areaOfResidence' => $this->areaOfResidence,
            'addressOfResidence' => $this->addressOfResidence,
            'periodOfResidence' => $this->periodOfResidence,
            'passportValidUntil' => $this->passportValidUntil?->format('Y-m-d'),
            'passportIssuedBy' => $this->passportIssuedBy,
            'passportIssuedOn' => $this->passportIssuedOn?->format('Y-m-d'),
            'addressOfWork' => $this->addressOfWork,
            'nameOfFacility' => $this->nameOfFacility,
            'education' => $this->education,
            'specialty' => $this->specialty,
            'qualification' => $this->qualification,
            'martialStatus' => $this->martialStatus,
            'contractPeriod' => $this->contractPeriod,
            'contractType' => $this->contractType,
            'contractExtensionPeriod' => $this->contractExtensionPeriod,
            'salary' => $this->salary,
            'workingTime' => $this->workingTime,
            'workingDays' => $this->workingDays,
            'startContractDate' => $this->startContractDate?->format('Y-m-d'),
            'endContractDate' => $this->endContractDate?->format('Y-m-d'),
            'contractStatus' => $this->contract_status,
            'contractPeriodDate' => $this->contractPeriodDate?->format('Y-m-d'),
            'contractPeriodNumber' => $this->contractPeriodNumber,
            'date' => $this->date?->format('Y-m-d'),
            'dossierNumber' => $this->dossierNumber,
            'notes' => $this->notes,
            'quartal' => $this->quartal,
            'seasonal' => $this->seasonal,
            'addedBy' => $this->addedBy,
            'passportPath' => $this->passportPath,
            'passportName' => $this->passportName,
            'personPicturePath' => $this->personPicturePath,
            'personPictureName' => $this->personPictureName,
            'isCandidate' => $this->isCandidate(),
            'isEmployee' => $this->isEmployee(),
            'isSeasonalContract' => $this->isSeasonalContract(),
            'hasMedicalInsurance' => $this->has_medical_insurance,
            'hasArrival' => $this->has_arrival,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'company' => $this->whenLoaded('company'),
            'status' => $this->whenLoaded('status'),
            'position' => $this->whenLoaded('position'),
            'user' => $this->whenLoaded('user'),
            'type' => $this->whenLoaded('type'),
            'cases' => $this->whenLoaded('cases'),
            'statusHistories' => $this->whenLoaded('statusHistories'),
            'latestStatusHistory' => $this->whenLoaded('latestStatusHistory'),
            'categories' => $this->whenLoaded('categories'),
            'files' => $this->whenLoaded('files'),
            'agentCandidates' => $this->whenLoaded('agentCandidates'),
            'education' => $this->whenLoaded('education'),
            'experience' => $this->whenLoaded('experience'),
            'medicalInsurance' => $this->whenLoaded('medicalInsurance'),
            'arrival' => $this->whenLoaded('arrival'),

            // Conditional fields
            'agentFullName' => $this->when(isset($this->agentFullName), $this->agentFullName),
        ];
    }

    protected function shouldShowPersonalInfo(): bool
    {
        $userRole = auth()->user()->role_id ?? null;

        // Admin and super admin can see all info
        if (in_array($userRole, [1, 2])) {
            return true;
        }

        // Hide sensitive info for company users and owners
        if (in_array($userRole, [3, 5])) {
            return false;
        }

        // Agent can see their candidates' info
        return $userRole === 4;
    }
}
