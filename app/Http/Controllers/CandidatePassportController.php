<?php

namespace App\Http\Controllers;

use App\Models\CandidatePassport;
use App\Services\File\FileOperationService;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidatePassportController extends Controller
{
    use HasRolePermissions;

    /**
     * Store a newly created passport for a candidate.
     */
    public function store(Request $request, FileOperationService $fileOperationService): JsonResponse
    {
        if (!$this->canCreatePassports()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $rules = [
            'candidate_id' => 'required|exists:candidates,id',
            'passport_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'issuing_country' => 'nullable|string|max:255',
            'passport_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'notes' => 'nullable|string',
        ];

        // Only validate expiry_date > issue_date when both are provided
        if ($request->filled('issue_date') && $request->filled('expiry_date')) {
            $rules['expiry_date'] .= '|after:issue_date';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $candidateId = $request->candidate_id;

            $passportData = [
                'candidate_id' => $candidateId,
                'passport_number' => $request->passport_number,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'issuing_country' => $request->issuing_country,
                'notes' => $request->notes,
            ];

            // Handle file upload
            if ($request->hasFile('passport_file')) {
                $file = $request->file('passport_file');
                $directory = 'candidate/' . $candidateId . '/passport';
                $filePath = $fileOperationService->uploadFile($file, $directory);

                $passportData['file_path'] = $filePath;
                $passportData['file_name'] = $file->getClientOriginalName();
            }

            $passport = CandidatePassport::create($passportData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Passport saved successfully',
                'passport' => $passport,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating passport', [
                'candidate_id' => $candidateId ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error saving passport',
            ], 500);
        }
    }

    /**
     * Display passport for a specific candidate.
     */
    public function show($candidateId): JsonResponse
    {
        if (!$this->canViewPassports()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $passport = CandidatePassport::where('candidate_id', $candidateId)->first();

        if (!$passport) {
            return response()->json([
                'success' => true,
                'passport' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'passport' => $passport,
        ]);
    }

    /**
     * Update the specified passport.
     */
    public function update(Request $request, $id, FileOperationService $fileOperationService): JsonResponse
    {
        if (!$this->canUpdatePassports()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $rules = [
            'passport_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'issuing_country' => 'nullable|string|max:255',
            'passport_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'notes' => 'nullable|string',
        ];

        // Only validate expiry_date > issue_date when both are provided
        if ($request->filled('issue_date') && $request->filled('expiry_date')) {
            $rules['expiry_date'] .= '|after:issue_date';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $passport = CandidatePassport::findOrFail($id);
            $candidateId = $passport->candidate_id;

            $passport->passport_number = $request->passport_number;
            $passport->issue_date = $request->issue_date;
            $passport->expiry_date = $request->expiry_date;
            $passport->issuing_country = $request->issuing_country;
            $passport->notes = $request->notes;

            // Track old file path for cleanup after successful operations
            $oldFilePath = $passport->file_path;
            $shouldDeleteOldFile = false;

            // Handle file upload (replaces existing) - upload new file first before deleting old
            if ($request->hasFile('passport_file')) {
                $file = $request->file('passport_file');
                $directory = 'candidate/' . $candidateId . '/passport';
                $filePath = $fileOperationService->uploadFile($file, $directory);

                $passport->file_path = $filePath;
                $passport->file_name = $file->getClientOriginalName();
                $shouldDeleteOldFile = !empty($oldFilePath);
            }

            $passport->save();

            DB::commit();

            // Delete old file only after successful commit
            if ($shouldDeleteOldFile && $oldFilePath) {
                Storage::disk('public')->delete($oldFilePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Passport updated successfully',
                'passport' => $passport,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating passport', [
                'passport_id' => $id,
                'candidate_id' => $candidateId ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating passport',
            ], 500);
        }
    }

    /**
     * Download the passport document.
     */
    public function download($id): StreamedResponse|JsonResponse
    {
        if (!$this->canViewPassports()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $passport = CandidatePassport::findOrFail($id);

        if (!$passport->file_path || !Storage::disk('public')->exists($passport->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        return Storage::disk('public')->download($passport->file_path, $passport->file_name);
    }

    /**
     * View/stream the passport document.
     */
    public function view($id): StreamedResponse|JsonResponse
    {
        if (!$this->canViewPassports()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $passport = CandidatePassport::findOrFail($id);

        if (!$passport->file_path || !Storage::disk('public')->exists($passport->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        $mimeType = Storage::disk('public')->mimeType($passport->file_path);

        return response()->stream(
            function () use ($passport) {
                echo Storage::disk('public')->get($passport->file_path);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $passport->file_name . '"',
            ]
        );
    }
}
