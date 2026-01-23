<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Permission;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    use HasRolePermissions;

    /**
     * Get paginated list of email logs with filters.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->canViewEmailLogs()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validate request parameters
        $validStatuses = [EmailLog::STATUS_QUEUED, EmailLog::STATUS_SENT, EmailLog::STATUS_FAILED];
        $validTypes = [
            EmailLog::TYPE_PASSWORD_RESET,
            EmailLog::TYPE_WELCOME_SET_PASSWORD,
            EmailLog::TYPE_DOCUMENT_SHARE,
            EmailLog::TYPE_UNPAID_INVOICES,
            EmailLog::TYPE_ARRIVAL_NOTIFICATION,
            EmailLog::TYPE_ARRIVAL_COMPANY_NOTIFICATION,
            EmailLog::TYPE_STATUS_NOTIFICATION,
            EmailLog::TYPE_CONTRACT_EXPIRY_REMINDER,
            EmailLog::TYPE_VISA_EXPIRY_REMINDER,
        ];
        $validSortColumns = ['id', 'recipient_email', 'status', 'email_type', 'created_at', 'sent_at', 'failed_at'];

        $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', $validStatuses)],
            'email_type' => ['nullable', 'string', 'in:' . implode(',', $validTypes)],
            'recipient_email' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:' . implode(',', $validSortColumns)],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = EmailLog::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by email type
        if ($request->filled('email_type')) {
            $query->where('email_type', $request->email_type);
        }

        // Filter by recipient email (partial match)
        if ($request->filled('recipient_email')) {
            $query->where('recipient_email', 'like', '%' . $request->recipient_email . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get email log statistics.
     */
    public function statistics(): JsonResponse
    {
        if (!$this->canViewEmailLogs()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $byStatus = EmailLog::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byType = EmailLog::selectRaw('email_type, count(*) as count')
            ->groupBy('email_type')
            ->pluck('count', 'email_type')
            ->toArray();

        $last24h = EmailLog::where('created_at', '>=', now()->subDay())->count();

        $failedLast24h = EmailLog::where('created_at', '>=', now()->subDay())
            ->where('status', EmailLog::STATUS_FAILED)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => EmailLog::count(),
                'by_status' => $byStatus,
                'by_type' => $byType,
                'last_24h' => $last24h,
                'failed_last_24h' => $failedLast24h,
            ],
        ]);
    }

    /**
     * Get a single email log by ID.
     */
    public function show(int $id): JsonResponse
    {
        if (!$this->canViewEmailLogs()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $log = EmailLog::find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Email log not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get available email types for filtering.
     */
    public function types(): JsonResponse
    {
        if (!$this->canViewEmailLogs()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                EmailLog::TYPE_PASSWORD_RESET => 'Password Reset',
                EmailLog::TYPE_WELCOME_SET_PASSWORD => 'Welcome / Set Password',
                EmailLog::TYPE_DOCUMENT_SHARE => 'Document Share',
                EmailLog::TYPE_UNPAID_INVOICES => 'Unpaid Invoices Report',
                EmailLog::TYPE_ARRIVAL_NOTIFICATION => 'Arrival Notification',
                EmailLog::TYPE_ARRIVAL_COMPANY_NOTIFICATION => 'Arrival Company Notification',
                EmailLog::TYPE_STATUS_NOTIFICATION => 'Status Notification',
                EmailLog::TYPE_CONTRACT_EXPIRY_REMINDER => 'Contract Expiry Reminder',
                EmailLog::TYPE_VISA_EXPIRY_REMINDER => 'Visa Expiry Reminder',
            ],
        ]);
    }

    /**
     * Permission check helper.
     */
    protected function canViewEmailLogs(): bool
    {
        return $this->checkPermission(Permission::EMAIL_LOGS_READ);
    }
}
