<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\AgentServiceContract;
use App\Models\AgentContractPricing;
use App\Models\AgentInvoice;
use App\Models\AgentCandidate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AgentInvoiceService
{
    /**
     * Helper function to save agent invoice when candidate status changes
     *
     * @param int $candidateId
     * @param int $statusId
     * @param string $statusDate
     * @return void
     */
    public static function saveAgentInvoiceOnStatusChange($candidateId, $statusId, $statusDate)
    {
        // Convert date format if needed
        $formattedDate = self::formatDate($statusDate);

        $candidate = Candidate::with('activeContract')->find($candidateId);

        if (!$candidate || !$candidate->company_id || !$candidate->country_id) {
            return; // Candidate does not have a company or country assigned
        }

        $companyId = $candidate->company_id;
        $candidateCountryId = $candidate->country_id;

        // Get candidate's contract type from active contract
        $candidateContractTypeId = $candidate->activeContract?->contract_type_id;

        // Find the agent who added this candidate
        $agentCandidate = AgentCandidate::where('candidate_id', $candidateId)->first();

        if (!$agentCandidate) {
            Log::info("No agent found for candidate ID: {$candidateId}");
            return; // No agent assigned to this candidate
        }

        $agentId = $agentCandidate->user_id;

        // Get the active contract for the agent
        $activeContract = AgentServiceContract::getActiveContract($agentId);

        if (!$activeContract) {
            Log::warning("No active contract found for agent ID: {$agentId} when processing candidate ID: {$candidateId}");
            return; // No active contract for the agent
        }

        $agent_service_contract_id = $activeContract->id;

        // Get all pricing for the status (with agentServiceType and contractTypes relationships)
        $allContractPricing = AgentContractPricing::where('agent_service_contract_id', $agent_service_contract_id)
            ->where('status_id', $statusId)
            ->with(['status', 'agentServiceType', 'contractTypes'])
            ->get();

        if ($allContractPricing->isEmpty()) {
            return; // Nema cena za daden status
        }

        // Filter pricing based on country scope
        $contractPricing = $allContractPricing->filter(function($item) use ($candidateCountryId) {
            $countryScopeIds = is_string($item->countryScopeIds)
                ? json_decode($item->countryScopeIds, true)
                : $item->countryScopeIds;

            switch ($item->countryScopeType) {
                case 'include':
                    // Only include if candidate's country is in the list
                    return is_array($countryScopeIds) && in_array($candidateCountryId, $countryScopeIds);
                case 'exclude':
                    // Exclude if candidate's country is in the list
                    return !is_array($countryScopeIds) || !in_array($candidateCountryId, $countryScopeIds);
                case 'all':
                default:
                    return true;
            }
        });

        // Filter pricing based on company scope
        $contractPricing = $contractPricing->filter(function($item) use ($companyId) {
            $companyScopeIds = is_string($item->companyScopeIds)
                ? json_decode($item->companyScopeIds, true)
                : $item->companyScopeIds;

            switch ($item->companyScopeType) {
                case 'include':
                    // Only include if candidate's company is in the list
                    return is_array($companyScopeIds) && in_array($companyId, $companyScopeIds);
                case 'exclude':
                    // Exclude if candidate's company is in the list
                    return !is_array($companyScopeIds) || !in_array($companyId, $companyScopeIds);
                case 'all':
                default:
                    return true;
            }
        });

        // Filter pricing based on contract type scope
        // If pricing has NO contract types → applies to ALL contract types
        // If pricing HAS contract types → only apply if candidate's contract type matches
        $contractPricing = $contractPricing->filter(function($item) use ($candidateContractTypeId) {
            // If no contract types defined for this pricing, it applies to all
            if ($item->contractTypes->isEmpty()) {
                return true;
            }

            // If candidate has no contract type, skip pricings that require specific types
            if (!$candidateContractTypeId) {
                return false;
            }

            // Check if candidate's contract type is in the pricing's contract types
            return $item->contractTypes->contains('id', $candidateContractTypeId);
        });

        // Filter pricing based on qualification scope
        // 'all' → applies to all candidates
        // 'qualified' → only applies to qualified candidates (is_qualified = true)
        // 'unqualified' → only applies to unqualified candidates (is_qualified = false)
        $candidateIsQualified = (bool) ($candidate->is_qualified ?? false);
        $contractPricing = $contractPricing->filter(function($item) use ($candidateIsQualified) {
            $qualificationScope = $item->qualification_scope ?? 'all';

            switch ($qualificationScope) {
                case 'qualified':
                    return $candidateIsQualified === true;
                case 'unqualified':
                    return $candidateIsQualified === false;
                case 'all':
                default:
                    return true;
            }
        });

        if ($contractPricing->isEmpty()) {
            Log::info("No applicable pricing found for candidate ID: {$candidateId} with country_id: {$candidateCountryId}, company_id: {$companyId}, contract_type_id: {$candidateContractTypeId} and status_id: {$statusId}");
            return;
        }

        foreach ($contractPricing as $item) {
            $invoice = new AgentInvoice();
            $invoice->candidate_id = $candidateId;
            $invoice->company_id = $companyId;
            $invoice->agent_id = $agentId;
            $invoice->agent_service_contract_id = $agent_service_contract_id;
            $invoice->serviceTypeName = $item->agentServiceType ? $item->agentServiceType->name : null;
            $invoice->statusName = $item->status->nameOfStatus;
            $invoice->statusDate = $formattedDate;
            $invoice->price = $item->price;
            $invoice->invoiceStatus = AgentInvoice::INVOICE_STATUS_NOT_INVOICED;
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
