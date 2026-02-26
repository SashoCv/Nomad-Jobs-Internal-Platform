<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidatePassport;
use App\Models\CandidateVisa;
use App\Traits\ChecksCandidateDocumentAccess;
use App\Traits\HasRolePermissions;
use App\Traits\ResolvesFilePaths;
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
    use ChecksCandidateDocumentAccess;
    use HasRolePermissions;
    use ResolvesFilePaths;

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

        // Validate agent can only upload to their own candidates
        $user = Auth::user();
        if ($user->hasRole(Role::AGENT)) {
            $candidate = Candidate::find($request->candidate_id);
            if (!$candidate || $candidate->agent_id !== $user->id) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
        }

        if ($error = $this->validateCategoryOwnership($request->category_id, $request->candidate_id)) {
            return $error;
        }

        $file->fileName = $request->fileName;
        $file->candidate_id = $request->candidate_id;
        $file->contract_id = $request->contract_id;
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
        if ($this->isStaff()) {
            $categories = Category::with('visibleToRoles')
                ->where('candidate_id', $id)
                ->orderBy('id', 'asc')
                ->get();

            $files = File::with('category.visibleToRoles')
                ->where('candidate_id', $id)
                ->get();
        } else {
            // COMPANY_OWNER (5) should also see categories visible to COMPANY_USER (3)
            $visibilityRoleIds = [Auth::user()->role_id];
            if (Auth::user()->role_id === Role::COMPANY_OWNER) {
                $visibilityRoleIds[] = Role::COMPANY_USER;
            }

            $categories = Category::with('visibleToRoles')
                ->where('candidate_id', $id)
                ->whereHas('visibleToRoles', fn($q) => $q->whereIn('roles.id', $visibilityRoleIds))
                ->orderBy('id', 'asc')
                ->get();

            $categoryIds = $categories->pluck('id');

            $files = File::with('category.visibleToRoles')
                ->where('candidate_id', $id)
                ->whereIn('category_id', $categoryIds)
                ->get();
        }

        $candidatePassport = $this->isStaff() ? \App\Models\CandidatePassport::where('candidate_id', $id)->value('file_path') : null;

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

        if ($error = $this->validateCategoryOwnership($newCategoryId, $file->candidate_id)) {
            return $error;
        }

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
            if ($error = $this->validateCategoryOwnership($request->category_id, $file->candidate_id)) {
                return $error;
            }

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

    private function validateCategoryOwnership($categoryId, $candidateId)
    {
        if (!$categoryId || !$candidateId) {
            return null;
        }

        $exists = Category::where('id', $categoryId)
            ->where('candidate_id', $candidateId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Category does not belong to this candidate',
            ], 422);
        }

        return null;
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
        $fileIds = $request->input('file_ids', []);
        $passportId = $request->input('passport_id');
        $visaId = $request->input('visa_id');

        // Validate that at least one file is selected
        if (empty($fileIds) && !$passportId && !$visaId) {
            return response()->json(['message' => 'No files selected'], 400);
        }

        $user = Auth::user();
        $existingFiles = [];

        // Handle regular files
        if (!empty($fileIds) && is_array($fileIds)) {
            $files = File::whereIn('id', $fileIds)->get();

            // Permission check: filter files user is allowed to download
            $allowedFiles = $files->filter(function ($file) use ($user) {
                return $this->canAccessCandidateDocument($user, $file->candidate_id);
            });

            foreach ($allowedFiles as $file) {
                $relPath = $file->filePath ?? $file->file_path;
                $resolvedPath = $this->resolveFilePath($relPath, 'file', $file->id);

                if ($resolvedPath) {
                    $existingFiles[] = [
                        'path' => $resolvedPath,
                        'fileName' => $file->fileName,
                    ];
                }
            }
        }

        // Handle passport file with permission check
        if ($passportId) {
            $passport = CandidatePassport::find($passportId);
            if ($passport) {
                // Permission check for passport
                if ($this->canAccessCandidateDocument($user, $passport->candidate_id)) {
                    $resolvedPath = $this->resolveFilePath($passport->file_path, 'Passport', $passport->id);
                    if ($resolvedPath) {
                        $existingFiles[] = [
                            'path' => $resolvedPath,
                            'fileName' => $passport->file_name ?: 'passport_' . $passport->id,
                        ];
                    }
                } else {
                    Log::warning("User {$user->id} denied access to passport ID {$passportId}");
                }
            }
        }

        // Handle visa file with permission check
        if ($visaId) {
            $visa = CandidateVisa::find($visaId);
            if ($visa) {
                // Permission check for visa
                if ($this->canAccessCandidateDocument($user, $visa->candidate_id)) {
                    $resolvedPath = $this->resolveFilePath($visa->file_path, 'Visa', $visa->id);
                    if ($resolvedPath) {
                        $existingFiles[] = [
                            'path' => $resolvedPath,
                            'fileName' => $visa->file_name ?: 'visa_' . $visa->id,
                        ];
                    }
                } else {
                    Log::warning("User {$user->id} denied access to visa ID {$visaId}");
                }
            }
        }

        if (empty($existingFiles)) {
            return response()->json(['message' => 'No files found or access denied'], 404);
        }

        $zip = new ZipArchive;
        $zipFileName = 'selected_documents_' . time() . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $usedFileNames = [];

            foreach ($existingFiles as $fileData) {
                $path = $fileData['path'];
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $result = $this->generateUniqueFileName($fileData['fileName'], $extension, $usedFileNames);
                $usedFileNames = $result['usedNames'];

                $zip->addFile($path, $result['fileName']);
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
