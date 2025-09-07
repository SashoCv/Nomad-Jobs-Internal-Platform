<?php

namespace App\Http\Transformers;

class TransformInvoice
{
    /**
     * Transform the invoice collection.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $invoices
     * @return array
     */
    public function transform($invoices, $allFilteredInvoices = null)
    {
        $transformed = $invoices->getCollection()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'candidate_id' => $invoice->candidate_id,
                'company_id' => $invoice->company_id,
                'company_service_contract_id' => $invoice->company_service_contract_id,
                'contract_service_type_id' => $invoice->contract_service_type_id,
                'statusName' => $invoice->statusName,
                'statusDate' => $invoice->statusDate,
                'price' => $invoice->price,
                'invoiceStatus' => $invoice->invoiceStatus,
                'notes' => $invoice->notes,
                'candidate' => $this->transformCandidate($invoice->candidate),
                'company' => $this->transformCompany($invoice->company),
                'companyServiceContract' => $this->transformCompanyServiceContract($invoice->companyServiceContract),
                'contractServiceType' => $this->transformContractServiceType($invoice->contractServiceType),
            ];
        });

        return [
            'data' => $transformed->toArray(),
            'current_page' => $invoices->currentPage(),
            'last_page' => $invoices->lastPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
            'from' => $invoices->firstItem(),
            'to' => $invoices->lastItem(),
            'summary' => $this->calculateSummary($allFilteredInvoices ?: $invoices->getCollection()),
        ];
    }

    /**
     * Calculate summary totals for invoices.
     *
     * @param \Illuminate\Support\Collection $invoices
     * @return array
     */
    private function calculateSummary($invoices)
    {
        $totalSum = $invoices->sum('price');
        $totalInvoiced = $invoices->where('invoiceStatus', 'invoiced')->sum('price');
        $totalNotInvoiced = $invoices->where('invoiceStatus', 'not_invoiced')->sum('price');

        return [
            'totalSum' => $totalSum,
            'totalInvoiced' => $totalInvoiced,
            'totalNotInvoiced' => $totalNotInvoiced,
        ];
    }

    /**
     * Transform the candidate.
     *
     * @param mixed $candidate
     * @return array|null
     */
    private function transformCandidate($candidate)
    {
        if (!$candidate) {
            return null;
        }

        return [
            'id' => $candidate->id,
            'fullName' => $candidate->fullName,
            'fullNameCyrillic' => $candidate->fullNameCyrillic,
            'contractType' => $candidate->contractType ?? null,
        ];
    }

    /**
     * Transform the company.
     *
     * @param mixed $company
     * @return array|null
     */
    private function transformCompany($company)
    {
        if (!$company) {
            return null;
        }

        return [
            'id' => $company->id,
            'nameOfCompany' => $company->nameOfCompany,
            'EIK' => $company->EIK ?? null,
        ];
    }

    /**
     * Transform the company service contract.
     *
     * @param mixed $companyServiceContract
     * @return array|null
     */
    private function transformCompanyServiceContract($companyServiceContract)
    {
        if (!$companyServiceContract) {
            return null;
        }

        return [
            'id' => $companyServiceContract->id,
            'contract_number' => $companyServiceContract->contractNumber ?? null,
        ];
    }

    /**
     * Transform the contract service type.
     *
     * @param mixed $contractServiceType
     * @return array|null
     */
    private function transformContractServiceType($contractServiceType)
    {
        if (!$contractServiceType) {
            return null;
        }

        return [
            'id' => $contractServiceType->id,
            'name' => $contractServiceType->name ?? null,
        ];
    }
}
