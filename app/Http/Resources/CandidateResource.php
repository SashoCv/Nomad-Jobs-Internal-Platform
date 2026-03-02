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
            'passport' => $this->passportRecord?->passport_number,
            'address' => $this->address,
            'birthday' => $this->birthday,
            'placeOfBirth' => $this->placeOfBirth,
            'country_id' => $this->country_id,
            'area' => $this->area,
            'areaOfResidence' => $this->areaOfResidence,
            'addressOfResidence' => $this->addressOfResidence,
            'periodOfResidence' => $this->periodOfResidence,
            'passportValidUntil' => $this->passportRecord?->expiry_date,
            'passportIssuedBy' => $this->passportRecord?->issued_by,
            'passportIssuedOn' => $this->passportRecord?->issue_date,
            'addressOfWork' => $this->activeContract?->address_of_work,
            'nameOfFacility' => $this->activeContract?->name_of_facility,
            'education' => $this->education,
            'specialty' => $this->specialty,
            'qualification' => $this->qualification,
            'martialStatus' => $this->martialStatus,
            'contractType' => $this->activeContract?->contract_type,
            'contract_type_id' => $this->activeContract?->contract_type_id,
            'contract_type' => $this->whenLoaded('activeContract', function () {
                $contractType = $this->activeContract?->getRelation('contract_type');
                return $contractType ? [
                    'id' => $contractType->id,
                    'name' => $contractType->name,
                    'slug' => $contractType->slug,
                ] : null;
            }),
            'salary' => $this->activeContract
                ? (string) $this->activeContract->getRawOriginal('salary')
                : null,
            'workingTime' => $this->activeContract?->working_time,
            'workingDays' => $this->activeContract?->working_days,
            'startContractDate' => $this->activeContract?->start_contract_date?->format('Y-m-d'),
            'endContractDate' => $this->activeContract?->end_contract_date?->format('Y-m-d'),
            'contractStatus' => $this->contract_status,
            'contractPeriodNumber' => $this->activeContract?->contract_period_number,
            'date' => $this->date,
            'dossierNumber' => $this->activeContract?->dossier_number,
            'notes' => $this->notes,
            'quartal' => $this->quartal,
            'seasonal' => $this->seasonal,
            'addedBy' => $this->addedBy,
            'passportPath' => $this->passportRecord?->file_path,
            'passportName' => $this->passportRecord?->file_name,
            'personPicturePath' => $this->personPicturePath,
            'personPictureName' => $this->personPictureName,
            'isCandidate' => $this->isCandidate(),
            'isEmployee' => $this->isEmployee(),
            'isSeasonalContract' => $this->isSeasonalContract(),
            'hasMedicalInsurance' => $this->has_medical_insurance,
            'hasArrival' => $this->has_arrival,
            'company_adresses_id' => $this->company_adresses_id,
            'workAddressCity' => $this->work_address_city,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'passportRecord' => $this->whenLoaded('passportRecord'),
            'company' => $this->whenLoaded('company'),
            'country' => $this->whenLoaded('country'),
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
            'activeContract' => $this->whenLoaded('activeContract'),
            'contracts' => $this->whenLoaded('contracts'),

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
