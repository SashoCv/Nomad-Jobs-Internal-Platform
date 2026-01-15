<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareDocumentsRequest;
use App\Mail\DocumentShareMail;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DocumentShareController extends Controller
{
    public function share(ShareDocumentsRequest $request)
    {
        $data = $request->validated();
        
        $files = File::whereIn('id', $data['file_ids'])->get();
        $filePaths = [];

        foreach ($files as $file) {
            $relPath = $file->filePath ?? $file->file_path;

            if (!$relPath) {
                \Illuminate\Support\Facades\Log::warning("No file path found for file ID {$file->id}");
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
                     \Illuminate\Support\Facades\Log::warning("File not found for ID {$file->id}: " . $relPath);
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
