<?php

namespace App\Services\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileOperationService
{
    /**
     * Upload a file to storage
     */
    public function uploadFile(UploadedFile $file, string $folder, string $disk = 'public'): string
    {
        // Generate a safe unique filename to prevent collisions and enumeration
        $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
        
        return $file->storeAs($folder, $fileName, $disk);
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        
        // Return true if file doesn't exist (it's effectively deleted)
        return true;
    }

    /**
     * Copy a file within storage
     */
    public function copyFile(string $sourcePath, string $destinationFolder, string $disk = 'public'): string|false
    {
        if (!Storage::disk($disk)->exists($sourcePath)) {
            return false;
        }

        $originalName = basename($sourcePath);
        // Remove UUID prefix if present from original name for clean copy
        // Or just generate new UUID
        // Strategy: Just create new unique name to avoid collision
        $newFileName = Str::uuid() . '_' . $originalName;
        $newPath = $destinationFolder . '/' . $newFileName;

        if (Storage::disk($disk)->copy($sourcePath, $newPath)) {
            return $newPath;
        }

        return false;
    }
}
