<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Helper function to save invoice when candidate status changes
     *
     * @param int $candidateId
     * @param int $statusId
     * @param string $statusDate
     * @return void
     */
    public static function saveInvoiceOnStatusChange($candidateId, $statusId, $statusDate)
    {
        $formattedDate = self::formatDate($statusDate);

        $candidate = Candidate::with('contract_type')->find($candidateId);

        if (!$candidate || !$candidate->company_id) {
            return;
        }

        $companyId = $candidate->company_id;
        $candidateCountryId = $candidate->country_id;
        $candidateContractTypeId = $candidate->contract_type_id;

        // Get the active contract for the company (one per company now)
        $activeContract = CompanyServiceContract::getActiveContract($companyId);

        if (!$activeContract) {
            Log::warning("No active contract found for company ID: {$companyId} when processing candidate ID: {$candidateId}");
            return;
        }

        $company_service_contract_id = $activeContract->id;

        // Get all pricing for the status, eager load contractTypes pivot and status
        $allContractPricing = ContractPricing::with(['contractTypes', 'status'])
            ->where('company_service_contract_id', $company_service_contract_id)
            ->where('status_id', $statusId)
            ->get();

        if ($allContractPricing->isEmpty()) {
            return;
        }

        // Filter pricing based on:
        // 1. contract_type match (empty = all types, otherwise must match candidate's contract_type_id)
        // 2. country_scope match
        $contractPricing = $allContractPricing->filter(function ($item) use ($candidateCountryId, $candidateContractTypeId) {
            // Contract type filter: if pricing has contract types, candidate must match one
            if ($item->contractTypes->isNotEmpty()) {
                // Pricing is restricted to specific contract types
                if (!$candidateContractTypeId) {
                    return false; // Candidate has no contract type — can't match restricted pricing
                }
                if (!$item->contractTypes->contains('id', $candidateContractTypeId)) {
                    return false; // Candidate's contract type doesn't match
                }
            }
            // If pricing has no contract types → applies to all (pass through)

            // Country scope filter
            $scopeType = $item->country_scope_type ?? 'all';
            $scopeIds = $item->country_scope_ids ?? [];

            if ($scopeType === 'all' || empty($scopeIds)) {
                return true;
            }

            $isInScope = in_array($candidateCountryId, $scopeIds);

            return $scopeType === 'include' ? $isInScope : !$isInScope;
        });

        if ($contractPricing->isEmpty()) {
            Log::info("No applicable pricing found for candidate ID: {$candidateId} with country_id: {$candidateCountryId}, contract_type_id: {$candidateContractTypeId} and status_id: {$statusId}");
            return;
        }

        foreach ($contractPricing as $item) {
            $invoice = new Invoice();
            $invoice->candidate_id = $candidateId;
            $invoice->company_id = $companyId;
            $invoice->company_service_contract_id = $company_service_contract_id;
            $invoice->contract_service_type_id = $item->contract_service_type_id;
            $invoice->statusName = $item->status->nameOfStatus;
            $invoice->statusDate = $formattedDate;
            $invoice->price = $item->price;
            $invoice->invoiceStatus = Invoice::INVOICE_STATUS_NOT_INVOICED;
            $invoice->notes = $item->description ?? null;
            $invoice->save();
        }
    }

    /**
     * Format date to Y-m-d format for database storage
     *
     * @param string $date
     * @return string
     */
    private static function formatDate($date)
    {
        try {
            // Try different date formats
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                // Format: dd-mm-yyyy or mm-dd-yyyy
                return Carbon::createFromFormat('m-d-Y', $date)->format('Y-m-d');
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // Already in Y-m-d format
                return $date;
            } else {
                // Try to parse as is
                return Carbon::parse($date)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Fallback to today's date if parsing fails
            return Carbon::now()->format('Y-m-d');
        }
    }
}
