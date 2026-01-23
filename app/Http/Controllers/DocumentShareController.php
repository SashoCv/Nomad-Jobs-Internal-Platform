<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareDocumentsRequest;
use App\Mail\DocumentShareMail;
use App\Models\EmailLog;
use App\Models\File;
use App\Models\CompanyFile;
use App\Models\User;
use App\Services\EmailTrackingService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentShareController extends Controller
{
    public function share(ShareDocumentsRequest $request, EmailTrackingService $trackingService)
    {
        $data = $request->validated();

        $fileType = $data['file_type'] ?? 'candidate';

        // Fetch files from the appropriate table
        if ($fileType === 'company') {
            $files = CompanyFile::whereIn('id', $data['file_ids'])->get();
        } else {
            $files = File::whereIn('id', $data['file_ids'])->get();
        }

        $filePaths = [];

        foreach ($files as $file) {
            $relPath = $file->filePath ?? $file->file_path;

            if (!$relPath) {
                Log::warning("No file path found for {$fileType} file ID {$file->id}");
                continue;
            }

            $path = public_path('storage/' . $relPath);

            if (file_exists($path) && is_file($path)) {
                 $filePaths[] = $path;
            } else {
                 $storagePath = storage_path('app/public/' . $relPath);

                 if (file_exists($storagePath) && is_file($storagePath)) {
                      $filePaths[] = $storagePath;
                 } else {
                     Log::warning("File not found for {$fileType} ID {$file->id}: " . $relPath);
                 }
            }
        }

        $recipients = $data['recipients'];
        $subject = $data['subject'] ?? 'Documents Shared with You';
        $message = $data['message'] ?? '';

        $emails = [];
        if ($data['recipient_type'] === 'internal') {
            $users = User::whereIn('id', $recipients)->get();
            $emails = $users->pluck('email')->toArray();
        } else {
            $emails = $recipients; // Assumed valid emails
        }

        foreach ($emails as $email) {
            // Log email for tracking
            $logId = $trackingService->logEmail(
                recipientEmail: $email,
                subject: $subject,
                emailType: EmailLog::TYPE_DOCUMENT_SHARE,
                metadata: [
                    'file_type' => $fileType,
                    'file_count' => count($filePaths),
                    'recipient_type' => $data['recipient_type'],
                ]
            );

            try {
                Mail::to($email)->queue(new DocumentShareMail($subject, $message, $filePaths));
                // Note: Since this is queued, we mark as sent immediately
                // The actual send happens in the queue worker
                $trackingService->markSent($logId);
            } catch (\Exception $e) {
                $trackingService->markFailed($logId, $e->getMessage());
                Log::error("Failed to queue document share email to {$email}: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Documents sent successfully.']);
    }
}
