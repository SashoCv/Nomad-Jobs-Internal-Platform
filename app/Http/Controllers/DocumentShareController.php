<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareDocumentsRequest;
use App\Mail\DocumentShareMail;
use App\Models\File;
use App\Models\CompanyFile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentShareController extends Controller
{
    public function share(ShareDocumentsRequest $request)
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
            Mail::to($email)->queue(new DocumentShareMail($subject, $message, $filePaths));
        }

        return response()->json(['message' => 'Documents sent successfully.']);
    }
}
