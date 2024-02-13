<?php

namespace App\Repository;

use App\Models\User;
use App\Notifications\CompanyJobCreatedNotification;
use Illuminate\Support\Facades\Notification;

class SendEmailRepositoryForCreateCompanyJob
{
    public function __construct()
    {
        // 
    }

    public function sendEmail($emailData)
    {
        $notification = new CompanyJobCreatedNotification($emailData);
        $users = User::where('id', '=', 12)->get();
        Notification::send($users, $notification);
    }
}
