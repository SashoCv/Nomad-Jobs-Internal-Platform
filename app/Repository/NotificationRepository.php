<?php

namespace App\Repository;

use App\Models\Notification;

class NotificationRepository
{
    public function __construct()
    {
        // 
    }

    public static function createNotification($notificationData)
    {
        $notification = new Notification();
        $notification->notification_message = $notificationData['message'];
        $notification->notification_type = $notificationData['type'];
        $notification->save();

        return $notification->id;
    }
}