<?php

namespace App\Http\Transformers;

use Carbon\Carbon;

class TransformCompanyServiceContract
{
    /**
     * Transform the company service contracts collection.
     *
     * @param \Illuminate\Support\Collection $contracts
     * @return array
     */
    public function transform($contracts)
    {
        return $contracts->map(function ($contract) {
            return [
               'id' => $contract->id,
                'contractNumber' => $contract->contractNumber,
                'agreementType' => $contract->agreement_type,
                'status' => $contract->status,
                'company' => $contract->company ? [
                    'id' => $contract->company->id,
                    'name' => $contract->company->nameOfCompany,
                ] : null,
                'contractPricing' => $contract->contractPricing->map(function ($pricing) {
                    return [
                        'id' => $pricing->id,
                        'price' => $pricing->price,
                        'contractServiceType' => [
                            'id' => $pricing->contractServiceType ? $pricing->contractServiceType->id : null,
                            'name' => $pricing->contractServiceType ? $pricing->contractServiceType->name : null,
                        ],
                        'status' => [
                            'id' => $pricing->status ? $pricing->status->id : null,
                            'nameOfStatus' => $pricing->status ? $pricing->status->nameOfStatus : null,
                        ],
                        'description' => $pricing->description,
                    ];
                })->toArray(),
                'companyContractFiles' => $contract->company ? $this->checkContractFiles($contract->company->id) : false,
            ];
        })->toArray();
    }


    public function checkContractFiles($companyId)
    {
        $files = \App\Models\CompanyFile::where('company_id', $companyId)->get();

        if ($files->isEmpty()) {
            return false;
        }

        foreach ($files as $file) {
           if($file->fileName == 'Contract File') {
               return true;
           }
        }

        return false;
    }
}
