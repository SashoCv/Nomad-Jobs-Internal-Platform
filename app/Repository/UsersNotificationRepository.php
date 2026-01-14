<?php

namespace App\Repository;

use App\Models\User;
use App\Models\UserNotification;

class UsersNotificationRepository
{
    public function __construct()
    {
    }

    public static function createNotificationForUsers($id)
    {
        $allUsersAdminsAndFromNomadOffice = User::whereNotIn('role_id', [3,4,5])->get();

        foreach ($allUsersAdminsAndFromNomadOffice as $user) {
            $notification = new UserNotification();
            $notification->user_id = $user->id;
            $notification->notification_id = $id;
            $notification->is_read = 0;
            $notification->save();
        }
    }
}
