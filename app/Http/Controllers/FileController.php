<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Traits\HasRolePermissions;
use App\Models\Category;
use App\Models\File;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Services\File\FileOperationService;

class FileController extends Controller
{
    use HasRolePermissions;

    public function __construct(
        protected FileOperationService $fileService
    ) {}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $documentsThatCanBeViewedByCompany = DB::table('files')
            ->join('categories', 'files.category_id', '=', 'categories.id')
            ->select('files.id', 'files.fileName', 'files.category_id', 'categories.nameOfCategory', 'files.company_restriction')
            ->where('files.fileName', 'like', 'ТД%')
            ->orWhere('files.fileName', 'like', 'ТРУДОВ%')
            ->orWhere('files.fileName', 'like', '%passport%')
            ->orWhere(function ($query) {
                $query->whereRaw('LOWER(categories.nameOfCategory) = LOWER(?)', ['ВИЗА']);
            })
            ->get();

        foreach ($documentsThatCanBeViewedByCompany as $document) {
            DB::table('files')
                ->where('id', $document->id)
                ->update(['company_restriction' => 0]);
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $documentsThatCanBeViewedByCompany
        ]);
    }

    public function downloadAllFile($id)
    {
        $candidate_id = $id;
        $candidate = Candidate::where('id', $candidate_id)->first();

        $files = File::where('candidate_id', $candidate_id)
            ->get(["filePath", "fileName"]);

        // Check if there are no files
        if ($files->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Няма документи за този кандидат'
            ], 404);
        }

        // Check if any files actually exist on disk
        $existingFiles = [];
        foreach ($files as $file) {
            $filePath = public_path('storage/' . $file->filePath);
            if (file_exists($filePath)) {
                $existingFiles[] = $file;
            }
        }

        if (empty($existingFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'Няма документи за този кандидат'
            ], 404);
        }

        $zip = new ZipArchive;
        $zipFileName = $candidate->fullName . '_documents.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($existingFiles as $file) {
                $filePath = public_path('storage/' . $file->filePath);
                $fileName = $file->fileName;
                $fileExtension = substr(strrchr($filePath, '.'), 1);
                $fileName .= '.' . $fileExtension;
                $zip->addFile($filePath, $fileName);
            }
            $zip->close();

            return response()->download($zipFilePath, $zipFileName);
        } else {
            return response()->json(['message' => 'Failed to create the zip file'], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Debug logging
        Log::info('[DEBUG FileController store] hasFile: ' . ($request->hasFile('file') ? 'true' : 'false'));
        Log::info('[DEBUG FileController store] fileName: ' . $request->fileName);
        Log::info('[DEBUG FileController store] all files keys: ' . json_encode(array_keys($request->allFiles())));

        // Try to get file directly
        $uploadedFile = $request->file('file');
        Log::info('[DEBUG FileController store] file() result: ' . ($uploadedFile ? 'exists' : 'null'));

        if ($uploadedFile) {
            Log::info('[DEBUG FileController store] file isValid: ' . ($uploadedFile->isValid() ? 'true' : 'false'));
            Log::info('[DEBUG FileController store] file getError: ' . $uploadedFile->getError());
            Log::info('[DEBUG FileController store] file getSize: ' . $uploadedFile->getSize());
            Log::info('[DEBUG FileController store] file getPath: ' . $uploadedFile->getPath());
            Log::info('[DEBUG FileController store] file getRealPath: ' . $uploadedFile->getRealPath());
        }

        $file = new File();

        // Use file() directly instead of hasFile() check
        if ($uploadedFile && $uploadedFile->isValid()) {
            Log::info('[DEBUG FileController store] file size: ' . $uploadedFile->getSize());
            Log::info('[DEBUG FileController store] file mime: ' . $uploadedFile->getMimeType());

            $name = $this->fileService->uploadFile($uploadedFile, 'files');
            $file->filePath = $name;
        } else {
            // No file uploaded - return error instead of proceeding
            $errorMsg = $uploadedFile ? 'File invalid, error: ' . $uploadedFile->getError() : 'No file received';
            Log::error('[DEBUG FileController store] ' . $errorMsg);
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No file was uploaded. ' . $errorMsg,
            ], 400);
        }

        $file->fileName = $request->fileName;
        $file->candidate_id = $request->candidate_id;
        $file->category_id = $request->category_id;
        $file->company_restriction = $request->company_restriction ?? 1;
        $file->autoGenerated = $request->autoGenerated;
        $file->deleteFile = $request->deleteFile;


        if ($file->save()) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => "Файлът \"{$file->fileName}\" беше качен успешно",
                'data' => $file
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Грешка при качване на файла',
                'data' => $file->errors()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function downloadFile(File $file)
    {
        $pathToFile = public_path('storage/' . $file->filePath);
        $extension = pathinfo($file->filePath, PATHINFO_EXTENSION);
        $fileName = $file->fileName;

        // Add extension if not already present
        if ($extension && !str_ends_with(strtolower($fileName), '.' . strtolower($extension))) {
            $fileName .= '.' . $extension;
        }

        return response()->download($pathToFile, $fileName);
    }

    /**
     * View/stream a file inline (for preview).
     *
     * @param  \App\Models\File  $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function viewFile(File $file)
    {
        if (!$file->filePath || !Storage::disk('public')->exists($file->filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        $mimeType = Storage::disk('public')->mimeType($file->filePath);
        $extension = pathinfo($file->filePath, PATHINFO_EXTENSION);
        $fileName = $file->fileName;

        // Add extension if not already present
        if ($extension && !str_ends_with(strtolower($fileName), '.' . strtolower($extension))) {
            $fileName .= '.' . $extension;
        }

        return response()->stream(
            function () use ($file) {
                echo Storage::disk('public')->get($file->filePath);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $userRoleId = Auth::user()->role_id;

        if ($this->isStaff()) {
            // Staff can see all categories for this candidate
            $categories = Category::where('candidate_id', $id)
                ->orderBy('id', 'asc')
                ->get();
        } else {
            // Non-staff users see categories where:
            // 1. role_id matches their role, OR
            // 2. Their role is in allowed_roles array
            $categories = Category::where('candidate_id', $id)
                ->where(function ($query) use ($userRoleId) {
                    $query->where('role_id', '=', $userRoleId)
                          ->orWhereJsonContains('allowed_roles', (string)$userRoleId)
                          ->orWhereJsonContains('allowed_roles', $userRoleId);
                })
                ->orderBy('id', 'asc')
                ->get();
        }

        $categoriesIds = $categories->pluck('id');
        
        // Files are filtered to only those in visible categories
        if ($this->isStaff()) {
            // Staff can see all files for this candidate
            $files = File::with('category')
                ->where('candidate_id', $id)
                ->get();
        } else {
            // Non-staff users only see files in their visible categories
            $files = File::with('category')
                ->where('candidate_id', $id)
                ->whereIn('category_id', $categoriesIds)
                ->get();
        }

        $candidatePassport = $this->isStaff() ? Candidate::where('id', $id)->value('passportPath') : null;

        return response()->json([
            'success' => true,
            'status' => 200,
            'files' => $files,
            'categories' => $categories,
            'candidatePassport' => $candidatePassport,
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function duplicate(Request $request, $id)
    {
        $file = File::findOrFail($id);
        $newCategoryId = $request->input('category_id');
        $originalPath = $file->filePath;
        
        $newPath = $this->fileService->copyFile($originalPath, 'files');

        if ($newPath) {
            $newFile = $file->replicate();
            $newFile->filePath = $newPath;
            $newFile->category_id = $newCategoryId;
            $newFile->save();
            return response()->json(['success' => true, 'data' => $newFile]);
        }
        return response()->json(['error' => 'Copy failed'], 500);
    }

    public function update(Request $request, $id)
    {
        $file = File::findOrFail($id);

        if ($request->has("category_id")) {
            $file->category_id = $request->category_id;
        }

        if ($request->has("fileName")) {
            $file->fileName = $request->fileName;
        }

        if ($file->save()) {
            return response()->json([
                "success" => true,
                "status" => 200,
                "message" => "Файлът беше преименуван успешно",
                "data" => $file
            ]);
        }

        return response()->json(["error" => "Update failed"], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $fileDelete = File::findOrFail($id);

        // Staff with DOCUMENTS_DELETE permission can delete any document
        if ($this->checkPermission(Permission::DOCUMENTS_DELETE)) {
            return $this->deleteFile($fileDelete);
        }

        // Agents can only delete documents for their own candidates
        if ($user->hasRole(Role::AGENT)) {
            $candidate = Candidate::find($fileDelete->candidate_id);
            if ($candidate && $candidate->agent_id === $user->id) {
                return $this->deleteFile($fileDelete);
            }
        }

        return response()->json(['error' => 'Insufficient permissions'], 403);
    }

    /**
     * Helper method to delete file and remove from storage
     */
    private function deleteFile(File $file)
    {
        $fileName = $file->fileName;

        if ($file->delete()) {
            if ($file->filePath) {
                $this->fileService->deleteFile($file->filePath);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => "Файлът \"{$fileName}\" беше изтрит успешно",
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => 500,
            'message' => 'Грешка при изтриване на файла',
        ], 500);
    }
    /**
     * Download multiple selected files as a ZIP archive.
     */
    public function downloadSelected(Request $request)
    {
        $fileIds = $request->input('file_ids');
        
        if (empty($fileIds) || !is_array($fileIds)) {
            return response()->json(['message' => 'No files selected'], 400);
        }

        $files = File::whereIn('id', $fileIds)->get();

        if ($files->isEmpty()) {
            return response()->json(['message' => 'No files found'], 404);
        }

        // Permission check: filter files user is allowed to download
        $user = Auth::user();
        $allowedFiles = $files->filter(function ($file) use ($user) {
            // Staff with DOCUMENTS_DELETE permission can download any document
            if ($this->checkPermission(Permission::DOCUMENTS_DELETE)) {
                return true;
            }
            
            // Agents can only download documents for their own candidates
            if ($user->hasRole(Role::AGENT)) {
                $candidate = Candidate::find($file->candidate_id);
                return $candidate && $candidate->agent_id === $user->id;
            }
            
            return false;
        });

        if ($allowedFiles->isEmpty()) {
            return response()->json(['message' => 'No files found or access denied'], 403);
        }

        // Check if any files actually exist on disk
        $existingFiles = [];
        foreach ($allowedFiles as $file) {
            $relPath = $file->filePath ?? $file->file_path;
            $filePath = public_path('storage/' . $relPath);
            
            if (file_exists($filePath)) {
                $existingFiles[] = $file;
            } else {
                 $storagePath = storage_path('app/public/' . $relPath);
                 if (file_exists($storagePath)) {
                      $file->resolvedPath = $storagePath;
                      $existingFiles[] = $file;
                 }
            }
        }

        if (empty($existingFiles)) {
             return response()->json(['message' => 'Selected files do not exist on server'], 404);
        }

        $zip = new ZipArchive;
        $zipFileName = 'selected_documents_' . time() . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($existingFiles as $file) {
                $path = $file->resolvedPath ?? public_path('storage/' . ($file->filePath ?? $file->file_path));
                
                $fileName = $file->fileName;
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                
                // Ensure extension is on the filename
                 if ($extension && !str_ends_with(strtolower($fileName), '.' . strtolower($extension))) {
                    $fileName .= '.' . $extension;
                }
                
                $zip->addFile($path, $fileName);
            }
            $zip->close();

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        } else {
            return response()->json(['message' => 'Failed to create the zip file'], 500);
        }
    }

    /**
     * Bulk delete selected files.
     */
    public function bulkDestroy(Request $request)
    {
        $fileIds = $request->input('file_ids');

        if (empty($fileIds) || !is_array($fileIds)) {
            return response()->json(['message' => 'No files selected'], 400);
        }

        $user = Auth::user();
        $deletedCount = 0;
        $errors = [];

        $files = File::whereIn('id', $fileIds)->get();

        foreach ($files as $file) {
            // Permission check (replicated from destroy method)
            $canDelete = false;

            if ($this->checkPermission(Permission::DOCUMENTS_DELETE)) {
                $canDelete = true;
            } elseif ($user->hasRole(Role::AGENT)) {
                $candidate = Candidate::find($file->candidate_id);
                if ($candidate && $candidate->agent_id === $user->id) {
                    $canDelete = true;
                }
            }

            if ($canDelete) {
                if ($file->delete()) {
                    $relPath = $file->filePath ?? $file->file_path;
                    if ($relPath) {
                        $this->fileService->deleteFile($relPath);
                    }
                    $deletedCount++;
                } else {
                    $errors[] = "Failed to delete file ID {$file->id}";
                }
            } else {
                $errors[] = "Permission denied for file ID {$file->id}";
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} files.",
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }
}
