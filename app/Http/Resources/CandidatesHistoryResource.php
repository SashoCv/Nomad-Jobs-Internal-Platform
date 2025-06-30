<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CandidatesHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'statusHistoryId' => $this->id,
            'status' => [
                'id' => $this->status->id,
                'name' => $this->status->nameOfStatus,
            ],
            'statusDate' => $this->statusDate,
            'candidate' => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->fullNameCyrillic,
                'email' => $this->candidate->email,
                'phone' => $this->candidate->phone,
                'company' => [
                    'id' => $this->candidate->company->id ?? null,
                    'name' => $this->candidate->company->nameOfCompany ?? null,
                ],
                'arrival' => [
                    'id' => $this->candidate->arrival->id ?? null,
                    'arrivalDate' => $this->candidate->arrival->arrival_date ?? null,
                    'arrivalTime' => $this->candidate->arrival->arrival_time ?? null,
                    'arrivalLocation' => $this->candidate->arrival->arrival_location ?? null,
                    'arrivalFlight' => $this->candidate->arrival->arrival_flight ?? null,
                    'whereToStay' => $this->candidate->arrival->where_to_stay ?? null,
                ],
            ],
        ];
    }
}
