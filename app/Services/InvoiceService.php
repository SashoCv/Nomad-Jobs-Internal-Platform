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
        // Convert date format if needed
        $formattedDate = self::formatDate($statusDate);

        $candidate = Candidate::with('contract_type')->find($candidateId);

        if (!$candidate || !$candidate->company_id || !$candidate->contract_type_id) {
            return; // Candidate does not have a company or contract type assigned
        }

        $companyId = $candidate->company_id;
        $candidateCountryId = $candidate->country_id;

        $contractType = self::mapContractType($candidate->contract_type?->slug);
        // Get the active contract for the company
        $activeContract = CompanyServiceContract::getActiveContract($companyId, $contractType);
        $company_service_contract_id = $activeContract ? $activeContract->id : null;

        if (!$company_service_contract_id) {
            \Log::warning("No active contract found for company ID: {$companyId} when processing candidate ID: {$candidateId}");
            return; // No active contract for the company
        }

        // Get all pricing for the status
        $allContractPricing = ContractPricing::where('company_service_contract_id', $company_service_contract_id)
            ->where('status_id', $statusId)
            ->get();

        if ($allContractPricing->isEmpty()) {
            return; // Nema cena za daden status
        }

        // Filter pricing based on country_scope_type + country_scope_ids
        $contractPricing = $allContractPricing->filter(function($item) use ($candidateCountryId) {
            $scopeType = $item->country_scope_type ?? 'all';
            $scopeIds = $item->country_scope_ids ?? [];

            if ($scopeType === 'all' || empty($scopeIds)) {
                return true;
            }

            $isInScope = in_array($candidateCountryId, $scopeIds);

            return $scopeType === 'include' ? $isInScope : !$isInScope;
        });

        if ($contractPricing->isEmpty()) {
            \Log::info("No applicable pricing found for candidate ID: {$candidateId} with country_id: {$candidateCountryId} and status_id: {$statusId}");
            return;
        }

        foreach ($contractPricing as $item){

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
     * Map candidate contract type to system contract type
     *
     * @param string $contractTypeCandidate
     * @return string
     */
    private static function mapContractType(?string $slug): ?string
    {
        if (!$slug) {
            return null;
        }

        if (in_array($slug, ['erpr1', 'erpr2', 'erpr3'])) {
            return 'erpr';
        }

        if (in_array($slug, ['9months', '90days'])) {
            return $slug;
        }

        return null;
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
