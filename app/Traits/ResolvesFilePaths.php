<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ResolvesFilePaths
{
    protected function resolveFilePath(?string $relPath, ?string $context = null, ?int $entityId = null): ?string
    {
        if (!$relPath) {
            if ($context && $entityId) {
                Log::warning("No file path found for {$context} ID {$entityId}");
            }
            return null;
        }

        $publicPath = public_path('storage/' . $relPath);
        if (file_exists($publicPath) && is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/' . $relPath);
        if (file_exists($storagePath) && is_file($storagePath)) {
            return $storagePath;
        }

        if ($context && $entityId) {
            Log::warning("{$context} file not found for ID {$entityId}: {$relPath}");
        }

        return null;
    }

    /**
     * @param array<int, string> $existingNames
     * @return array{fileName: string, usedNames: array<int, string>}
     */
    protected function generateUniqueFileName(string $baseName, string $extension, array $existingNames): array
    {
        $fileName = $baseName;

        if ($extension && !str_ends_with(strtolower($fileName), '.' . strtolower($extension))) {
            $fileName .= '.' . $extension;
        }

        $originalFileName = $fileName;
        $counter = 1;

        while (in_array(strtolower($fileName), array_map('strtolower', $existingNames))) {
            $pathInfo = pathinfo($originalFileName);
            $nameWithoutExt = $pathInfo['filename'];
            $ext = $pathInfo['extension'] ?? '';
            $fileName = "{$nameWithoutExt}_{$counter}" . ($ext ? ".{$ext}" : '');
            $counter++;
        }

        $existingNames[] = $fileName;

        return [
            'fileName' => $fileName,
            'usedNames' => $existingNames,
        ];
    }
}
