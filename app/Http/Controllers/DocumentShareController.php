<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareDocumentsRequest;
use App\Mail\DocumentShareMail;
use App\Models\EmailLog;
use App\Models\File;
use App\Models\CompanyFile;
use App\Models\CandidatePassport;
use App\Models\CandidateVisa;
use App\Models\User;
use App\Services\EmailTrackingService;
use App\Traits\ChecksCandidateDocumentAccess;
use App\Traits\ResolvesFilePaths;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentShareController extends Controller
{
    use ChecksCandidateDocumentAccess;
    use ResolvesFilePaths;

    public function share(ShareDocumentsRequest $request, EmailTrackingService $trackingService)
    {
        $data = $request->validated();
        $user = Auth::user();

        $fileType = $data['file_type'] ?? 'candidate';
        $fileIds = $data['file_ids'] ?? [];

        $filePaths = [];
        $accessDeniedCount = 0;

        // Fetch files from the appropriate table (if any file_ids provided)
        if (!empty($fileIds)) {
            if ($fileType === 'company') {
                $files = CompanyFile::whereIn('id', $fileIds)->get();
                // Company files - check company permission
                foreach ($files as $file) {
                    $relPath = $file->filePath ?? $file->file_path;
                    $resolvedPath = $this->resolveFilePath($relPath, 'company file', $file->id);
                    if ($resolvedPath) {
                        $filePaths[] = $resolvedPath;
                    }
                }
            } else {
                $files = File::whereIn('id', $fileIds)->get();
                // Candidate files - check permission for each
                foreach ($files as $file) {
                    if ($this->canAccessCandidateDocument($user, $file->candidate_id)) {
                        $relPath = $file->filePath ?? $file->file_path;
                        $resolvedPath = $this->resolveFilePath($relPath, 'candidate file', $file->id);
                        if ($resolvedPath) {
                            $filePaths[] = $resolvedPath;
                        }
                    } else {
                        $accessDeniedCount++;
                        Log::warning("User {$user->id} denied access to share file ID {$file->id}");
                    }
                }
            }
        }

        // Handle passport file if provided with permission check
        if (!empty($data['passport_id'])) {
            $passport = CandidatePassport::find($data['passport_id']);
            if ($passport) {
                if ($this->canAccessCandidateDocument($user, $passport->candidate_id)) {
                    $resolvedPath = $this->resolveFilePath($passport->file_path, 'Passport', $passport->id);
                    if ($resolvedPath) {
                        $filePaths[] = $resolvedPath;
                    }
                } else {
                    $accessDeniedCount++;
                    Log::warning("User {$user->id} denied access to share passport ID {$passport->id}, candidate {$passport->candidate_id}");
                }
            }
        }

        // Handle visa file if provided with permission check
        if (!empty($data['visa_id'])) {
            $visa = CandidateVisa::find($data['visa_id']);
            if ($visa) {
                if ($this->canAccessCandidateDocument($user, $visa->candidate_id)) {
                    $resolvedPath = $this->resolveFilePath($visa->file_path, 'Visa', $visa->id);
                    if ($resolvedPath) {
                        $filePaths[] = $resolvedPath;
                    }
                } else {
                    $accessDeniedCount++;
                    Log::warning("User {$user->id} denied access to share visa ID {$visa->id}, candidate {$visa->candidate_id}");
                }
            }
        }

        // Check if we have any files to share after permission filtering
        if (empty($filePaths)) {
            if ($accessDeniedCount > 0) {
                return response()->json(['message' => 'Access denied to selected files.'], 403);
            }
            return response()->json(['message' => 'No valid files found to share.'], 404);
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
                    'passport_id' => $data['passport_id'] ?? null,
                    'visa_id' => $data['visa_id'] ?? null,
                    'shared_by_user_id' => $user->id,
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
