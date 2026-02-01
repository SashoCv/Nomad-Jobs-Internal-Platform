<?php

namespace App\Http\Controllers;

use App\Models\AgentInvoice;
use App\Models\Permission;
use App\Models\Role;
use App\Traits\HasRolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentInvoiceController extends Controller
{
    use HasRolePermissions;

    /**
     * Display a listing of agent invoices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Check permission: FINANCE_READ for admins/finance, AGENT_INVOICES_READ for agents
        if (!$this->checkPermission(Permission::FINANCE_READ) && !$this->checkPermission(Permission::AGENT_INVOICES_READ)) {
            return response()->json(['error' => 'Access denied', 'message' => 'You do not have permission to view agent invoices'], 403);
        }

        try {
            $user = Auth::user();
            $query = AgentInvoice::with(['candidate', 'company', 'agent', 'agentServiceContract']);

            // If user is an agent, only show their own invoices that are invoiced or paid
            if ($user->role_id === Role::AGENT) {
                $query->where('agent_id', $user->id)
                      ->whereIn('invoiceStatus', [AgentInvoice::INVOICE_STATUS_INVOICED, AgentInvoice::INVOICE_STATUS_PAID]);
            }

            // Filter by candidate name (like) - search both Latin and Cyrillic names
            if ($request->filled('candidateName')) {
                $query->whereHas('candidate', function ($q) use ($request) {
                    $q->where(function ($subQuery) use ($request) {
                        $subQuery->where('fullName', 'like', '%' . $request->candidateName . '%')
                                 ->orWhere('fullNameCyrillic', 'like', '%' . $request->candidateName . '%');
                    });
                });
            }

            // Filter by company name (like)
            if ($request->filled('companyName')) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('nameOfCompany', 'like', '%' . $request->companyName . '%');
                });
            }

            // Filter by agent name (like)
            if ($request->filled('agentName')) {
                $query->whereHas('agent', function ($q) use ($request) {
                    $q->where(function ($subQuery) use ($request) {
                        $subQuery->where('firstName', 'like', '%' . $request->agentName . '%')
                                 ->orWhere('lastName', 'like', '%' . $request->agentName . '%');
                    });
                });
            }

            // Filter by invoice status
            if ($request->filled('invoiceStatus')) {
                $query->where('invoiceStatus', $request->invoiceStatus);
            }

            // Filter by date range
            if ($request->filled('dateFrom')) {
                $query->where('statusDate', '>=', $request->dateFrom);
            }

            if ($request->filled('dateTo')) {
                $query->where('statusDate', '<=', $request->dateTo);
            }

            // Filter by agent_id
            if ($request->filled('agent_id')) {
                $query->where('agent_id', $request->agent_id);
            }

            // Clone query for summary calculations (before pagination)
            $summaryQuery = clone $query;

            // Calculate summary statistics
            $allInvoices = $summaryQuery->get();
            $totalSum = $allInvoices->sum('price');
            $totalInvoiced = $allInvoices->where('invoiceStatus', AgentInvoice::INVOICE_STATUS_INVOICED)->sum('price');
            $totalNotInvoiced = $allInvoices->where('invoiceStatus', AgentInvoice::INVOICE_STATUS_NOT_INVOICED)->sum('price');
            $totalPaid = $allInvoices->where('invoiceStatus', AgentInvoice::INVOICE_STATUS_PAID)->sum('price');

            // For payment: sum of not_invoiced + invoiced (excluding paid and rejected)
            $remainingToPay = $totalNotInvoiced + $totalInvoiced;

            $summary = [
                'totalSum' => $totalSum,
                'totalInvoiced' => $totalInvoiced,
                'totalNotInvoiced' => $totalNotInvoiced,
                'totalPaid' => $totalPaid,
                'remainingToPay' => $remainingToPay,
            ];

            $agentInvoices = $query->orderBy('statusDate', 'desc')->paginate($request->get('per_page', 15));

            // Add summary to response
            $response = $agentInvoices->toArray();
            $response['summary'] = $summary;

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error retrieving agent invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve agent invoices', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an agent invoice
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $invoice = AgentInvoice::findOrFail($id);

            $validated = $request->validate([
                'invoiceStatus' => 'required|in:invoiced,not_invoiced,rejected,paid',
                'notes' => 'nullable|string',
                'invoice_number' => 'nullable|string',
            ]);

            $invoice->update($validated);

            return response()->json([
                'message' => 'Agent invoice updated successfully',
                'invoice' => $invoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating agent invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update agent invoice', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an agent invoice
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $invoice = AgentInvoice::findOrFail($id);
            $invoice->delete();

            return response()->json([
                'message' => 'Agent invoice deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting agent invoice: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete agent invoice', 'message' => $e->getMessage()], 500);
        }
    }
}
