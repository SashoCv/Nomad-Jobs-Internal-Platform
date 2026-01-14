<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function readNotification($id)
    {
        $userNotification = UserNotification::where('id', $id)->first();
        if (!$userNotification) {
            return response()->json(['message' => 'Notification not found']);
        }
        $userNotification->is_read = 1;
        $userNotification->save();
        return response()->json(['message' => 'Notification updated successfully']);
    }

    /**
     * Mark all notifications as read for the current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function markAllAsRead()
    {
        UserNotification::where('user_id', Auth::user()->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserNotification  $userNotification
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $userNotification = DB::table('user_notifications')
            ->join('notifications', 'user_notifications.notification_id', '=', 'notifications.id')
            ->where('user_notifications.user_id', Auth::user()->id)
            ->orderBy('user_notifications.id', 'desc')
            ->limit(30)
            ->get(['user_notifications.id','notifications.notification_message','notifications.notification_type','user_notifications.is_read','user_notifications.is_seen','user_notifications.created_at','user_notifications.updated_at']);

        if (!$userNotification) {
            return response()->json(['message' => 'No notification found']);
        }
        return response()->json($userNotification);
    }

    /**
     * Get paginated notifications for the notifications page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $readStatus = $request->input('read_status'); // 'read', 'unread', or null for all
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = DB::table('user_notifications')
            ->join('notifications', 'user_notifications.notification_id', '=', 'notifications.id')
            ->where('user_notifications.user_id', Auth::user()->id)
            ->orderBy('user_notifications.id', 'desc');

        // Filter by read status if provided
        if ($readStatus === 'read') {
            $query->where('user_notifications.is_read', 1);
        } elseif ($readStatus === 'unread') {
            $query->where('user_notifications.is_read', 0);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('user_notifications.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('user_notifications.created_at', '<=', $dateTo);
        }

        $notifications = $query->paginate($perPage, [
            'user_notifications.id',
            'notifications.notification_message',
            'notifications.notification_type',
            'user_notifications.is_read',
            'user_notifications.is_seen',
            'user_notifications.created_at',
            'user_notifications.updated_at'
        ]);

        // Get unread count
        $unreadCount = DB::table('user_notifications')
            ->where('user_id', Auth::user()->id)
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserNotification  $userNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(UserNotification $userNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserNotification  $userNotification
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $userNotifications = UserNotification::where('user_id', Auth::user()->id)->get();
        if (!$userNotifications) {
            return response()->json(['message' => 'No notification for this user found']);
        }
        foreach ($userNotifications as $userNotification) {
            $userNotification->is_seen = 1;
            $userNotification->save();
        }
        return response()->json(['message' => 'Notification updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserNotification  $userNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserNotification $userNotification)
    {
        //
    }
}
