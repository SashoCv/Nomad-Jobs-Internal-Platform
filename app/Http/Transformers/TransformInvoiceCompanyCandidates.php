<?php

namespace App\Http\Transformers;

use App\Models\InvoiceCompanyCandidate;

class TransformInvoiceCompanyCandidates
{
    /**
     * Transform the invoice company candidates collection.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @return array
     */
    public function transform($candidates)
    {
        return $candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'candidate' => $this->transformCandidate($candidate->candidate),
                'invoiceCompany' => $this->transformInvoiceCompany($candidate->invoiceCompany),
            ];
        })->toArray();
    }

    /**
     * Transform the invoice company.
     *
     * @param mixed $invoiceCompany
     * @return array
     */
    private function transformInvoiceCompany($invoiceCompany)
    {
        if (!$invoiceCompany) {
            return null;
        }

        return [
            'id' => $invoiceCompany->id,
            'invoice_number' => $invoiceCompany->invoice_number,
            'invoice_date' => $invoiceCompany->invoice_date,
            'status' => $invoiceCompany->status,
            'invoice_amount' => $invoiceCompany->invoice_amount,
            'payment_date' => $invoiceCompany->payment_date,
            'payment_amount' => $invoiceCompany->payment_amount,
            'is_paid' => $invoiceCompany->is_paid,
            'company' => $this->transformCompany($invoiceCompany->company),
            'items' => $this->transformItemInvoices($invoiceCompany->itemInvoice),
        ];
    }

    /**
     * Transform the company.
     *
     * @param mixed $company
     * @return array
     */
    private function transformCompany($company)
    {
        if (!$company) {
            return null;
        }

        return [
            'id' => $company->id,
            'nameOfCompany' => $company->nameOfCompany,
        ];
    }

    /**
     * Transform item invoices.
     *
     * @param mixed $itemInvoices
     * @return array
     */
    private function transformItemInvoices($itemInvoices)
    {
        return $itemInvoices->map(function ($itemInvoice) {
            return [
                'id' => $itemInvoice->id,
                'items_for_invoices_id' => $itemInvoice->items_for_invoices_id,
                'price' => $itemInvoice->price,
                'percentage' => $itemInvoice->percentage,
                'amount' => $itemInvoice->amount,
                'total' => $itemInvoice->total,
                'item' => $this->transformItemForInvoice($itemInvoice->itemForInvoice),
            ];
        })->toArray();
    }

    /**
     * Transform item for invoice.
     *
     * @param mixed $itemForInvoice
     * @return array
     */
    private function transformItemForInvoice($itemForInvoice)
    {
        if (!$itemForInvoice) {
            return null;
        }

        return [
            'id' => $itemForInvoice->id,
            'name' => $itemForInvoice->name,
        ];
    }

    /**
     * Transform the candidate.
     *
     * @param mixed $candidate
     * @return array
     */
    private function transformCandidate($candidate)
    {
        if (!$candidate) {
            return null;
        }

        return [
            'id' => $candidate->id,
            'fullName' => $candidate->fullName,
        ];
    }
}
