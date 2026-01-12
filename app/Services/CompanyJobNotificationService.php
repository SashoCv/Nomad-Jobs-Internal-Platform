<?php

namespace App\Services;

use App\Models\CompanyJob;
use App\Models\Company;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use App\Repository\SendEmailRepositoryForCreateCompanyJob;

class CompanyJobNotificationService
{
    public function __construct(
        private UsersNotificationRepository $usersNotificationRepository,
        private NotificationRepository $notificationRepository,
        private SendEmailRepositoryForCreateCompanyJob $sendEmailRepositoryForCreateCompanyJob
    ) {}

    public function sendCreatedNotification(CompanyJob $companyJob): void
    {
        $company = Company::find($companyJob->company_id);
        $companyName = $company->nameOfCompany;

        $notificationData = [
            'message' => $companyName . ' публикува нова обява: ' . $companyJob->job_title,
            'type' => 'job_posting'
        ];

        $this->createAndSendNotification($notificationData);
        
        try {
            $this->sendEmailRepositoryForCreateCompanyJob->sendEmail($companyJob);
        } catch (\Exception $e) {
            \Log::warning('Failed to send job creation email: ' . $e->getMessage());
        }
    }

    public function sendUpdatedNotification(CompanyJob $companyJob): void
    {
        $company = Company::find($companyJob->company_id);
        $companyName = $company->nameOfCompany;

        $notificationData = [
            'message' => $companyName . ' редактира обява: ' . $companyJob->job_title,
            'type' => 'job_posting_updated'
        ];

        $this->createAndSendNotification($notificationData);
    }

    private function createAndSendNotification(array $notificationData): void
    {
        $notification = NotificationRepository::createNotification($notificationData);
        UsersNotificationRepository::createNotificationForUsers($notification);
    }
}