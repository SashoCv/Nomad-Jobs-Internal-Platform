<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\CandidateVisa;
use App\Services\File\FileOperationService;
use App\Traits\HasRolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateVisaController extends Controller
{
    use HasRolePermissions;

    /**
     * Store a newly created visa for a candidate.
     */
    public function store(Request $request, FileOperationService $fileOperationService): JsonResponse
    {
        if (!$this->canCreateVisas()) {
            return response()->json(['error' => 'Нямате права да създавате визи'], 403);
        }

        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'visa_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'visa_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $candidateId = $request->candidate_id;
            $candidate = Candidate::findOrFail($candidateId);

            $visaData = [
                'candidate_id' => $candidateId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'visa_type' => $request->visa_type,
                'notes' => $request->notes,
            ];

            // Handle file upload - visa owns the document directly
            if ($request->hasFile('visa_file')) {
                $file = $request->file('visa_file');
                $directory = 'candidate/' . $candidateId . '/visa';
                $filePath = $fileOperationService->uploadFile($file, $directory);

                $visaData['file_path'] = $filePath;
                $visaData['file_name'] = $file->getClientOriginalName();
            }

            $visa = CandidateVisa::create($visaData);

            // Create calendar event for visa expiry
            CalendarEvent::updateOrCreate(
                [
                    'type' => CalendarEvent::TYPE_VISA_EXPIRY,
                    'candidate_id' => $candidateId,
                ],
                [
                    'title' => 'Изтичаща виза',
                    'date' => $request->end_date,
                    'company_id' => $candidate->company_id,
                    'created_by' => Auth::id(),
                    'description' => 'Визата изтича на ' . $request->end_date,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Визата беше записана успешно',
                'visa' => $visa,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating visa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Грешка при запазване на визата',
            ], 500);
        }
    }

    /**
     * Display visa for a specific candidate.
     */
    public function show($candidateId): JsonResponse
    {
        if (!$this->canViewVisas()) {
            return response()->json(['error' => 'Нямате права да преглеждате визи'], 403);
        }

        $visa = CandidateVisa::where('candidate_id', $candidateId)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$visa) {
            return response()->json([
                'success' => true,
                'visa' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'visa' => $visa,
        ]);
    }

    /**
     * Get all visas for a candidate (history).
     */
    public function history($candidateId): JsonResponse
    {
        if (!$this->canViewVisas()) {
            return response()->json(['error' => 'Нямате права да преглеждате визи'], 403);
        }

        $visas = CandidateVisa::where('candidate_id', $candidateId)
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'visas' => $visas,
        ]);
    }

    /**
     * Update the specified visa.
     */
    public function update(Request $request, $id, FileOperationService $fileOperationService): JsonResponse
    {
        if (!$this->canUpdateVisas()) {
            return response()->json(['error' => 'Нямате права да редактирате визи'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'visa_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'visa_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'remove_file' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $visa = CandidateVisa::findOrFail($id);
            $candidateId = $visa->candidate_id;
            $candidate = Candidate::findOrFail($candidateId);

            $visa->start_date = $request->start_date;
            $visa->end_date = $request->end_date;
            $visa->visa_type = $request->visa_type;
            $visa->notes = $request->notes;

            // Handle file removal
            if ($request->boolean('remove_file') && $visa->file_path) {
                Storage::disk('public')->delete($visa->file_path);
                $visa->file_path = null;
                $visa->file_name = null;
            }

            // Handle file upload (replaces existing)
            if ($request->hasFile('visa_file')) {
                // Delete old file if exists
                if ($visa->file_path) {
                    Storage::disk('public')->delete($visa->file_path);
                }

                $file = $request->file('visa_file');
                $directory = 'candidate/' . $candidateId . '/visa';
                $filePath = $fileOperationService->uploadFile($file, $directory);

                $visa->file_path = $filePath;
                $visa->file_name = $file->getClientOriginalName();
            }

            $visa->save();

            // Update calendar event
            CalendarEvent::updateOrCreate(
                [
                    'type' => CalendarEvent::TYPE_VISA_EXPIRY,
                    'candidate_id' => $candidateId,
                ],
                [
                    'title' => 'Изтичаща виза',
                    'date' => $request->end_date,
                    'company_id' => $candidate->company_id,
                    'created_by' => Auth::id(),
                    'description' => 'Визата изтича на ' . $request->end_date,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Визата беше обновена успешно',
                'visa' => $visa,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating visa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Грешка при обновяване на визата',
            ], 500);
        }
    }

    /**
     * Remove the specified visa.
     */
    public function destroy($id): JsonResponse
    {
        if (!$this->canDeleteVisas()) {
            return response()->json(['error' => 'Нямате права да изтривате визи'], 403);
        }

        try {
            DB::beginTransaction();

            $visa = CandidateVisa::findOrFail($id);
            $candidateId = $visa->candidate_id;

            // Delete the associated file if exists
            if ($visa->file_path) {
                Storage::disk('public')->delete($visa->file_path);
            }

            $visa->delete();

            // Remove calendar event if no more visas exist
            $remainingVisas = CandidateVisa::where('candidate_id', $candidateId)->count();
            if ($remainingVisas === 0) {
                CalendarEvent::where('type', CalendarEvent::TYPE_VISA_EXPIRY)
                    ->where('candidate_id', $candidateId)
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Визата беше изтрита успешно',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting visa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Грешка при изтриване на визата',
            ], 500);
        }
    }

    /**
     * Download the visa document.
     */
    public function download($id): StreamedResponse|JsonResponse
    {
        if (!$this->canViewVisas()) {
            return response()->json(['error' => 'Нямате права да преглеждате визи'], 403);
        }

        $visa = CandidateVisa::findOrFail($id);

        if (!$visa->file_path || !Storage::disk('public')->exists($visa->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Документът не е намерен',
            ], 404);
        }

        return Storage::disk('public')->download($visa->file_path, $visa->file_name);
    }

    /**
     * View/stream the visa document.
     */
    public function view($id): StreamedResponse|JsonResponse
    {
        if (!$this->canViewVisas()) {
            return response()->json(['error' => 'Нямате права да преглеждате визи'], 403);
        }

        $visa = CandidateVisa::findOrFail($id);

        if (!$visa->file_path || !Storage::disk('public')->exists($visa->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Документът не е намерен',
            ], 404);
        }

        $mimeType = Storage::disk('public')->mimeType($visa->file_path);

        return response()->stream(
            function () use ($visa) {
                echo Storage::disk('public')->get($visa->file_path);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $visa->file_name . '"',
            ]
        );
    }
}
