<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateCvPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateCvPhotoController extends Controller
{
    /**
     * Get all CV photos for a candidate
     */
    public function index(Candidate $candidate): JsonResponse
    {
        $photos = $candidate->cvPhotos()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'workplace' => $photos->where('type', CandidateCvPhoto::TYPE_WORKPLACE)->values(),
            'diploma' => $photos->where('type', CandidateCvPhoto::TYPE_DIPLOMA)->values(),
            'driving_license' => $photos->where('type', CandidateCvPhoto::TYPE_DRIVING_LICENSE)->first(),
        ]);
    }

    /**
     * Upload CV photos
     */
    public function store(Request $request, Candidate $candidate): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:workplace,diploma,driving_license',
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $type = $request->input('type');
        $uploadedPhotos = [];

        // For driving license, only allow one photo - delete existing if any
        if ($type === CandidateCvPhoto::TYPE_DRIVING_LICENSE) {
            $existing = $candidate->drivingLicensePhoto;
            if ($existing) {
                if (Storage::disk('public')->exists($existing->file_path)) {
                    Storage::disk('public')->delete($existing->file_path);
                }
                $existing->delete();
            }
        }

        $directory = 'candidate/' . $candidate->id . '/cv-photos/' . $type;
        $sortOrder = $candidate->cvPhotos()->where('type', $type)->max('sort_order') ?? 0;

        foreach ($request->file('files') as $file) {
            $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs($directory, $fileName, 'public');

            $photo = CandidateCvPhoto::create([
                'candidate_id' => $candidate->id,
                'type' => $type,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'sort_order' => ++$sortOrder,
            ]);

            $uploadedPhotos[] = $photo;

            // For driving license, only one file allowed
            if ($type === CandidateCvPhoto::TYPE_DRIVING_LICENSE) {
                break;
            }
        }

        return response()->json([
            'message' => 'Photos uploaded successfully',
            'photos' => $uploadedPhotos,
        ], 201);
    }

    /**
     * Delete a CV photo
     */
    public function destroy(CandidateCvPhoto $cvPhoto): JsonResponse
    {
        if (Storage::disk('public')->exists($cvPhoto->file_path)) {
            Storage::disk('public')->delete($cvPhoto->file_path);
        }

        $cvPhoto->delete();

        return response()->json([
            'message' => 'Photo deleted successfully',
        ]);
    }

    /**
     * Reorder photos
     */
    public function reorder(Request $request, Candidate $candidate): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:candidate_cv_photos,id',
            'photos.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('photos') as $photoData) {
            CandidateCvPhoto::where('id', $photoData['id'])
                ->where('candidate_id', $candidate->id)
                ->update(['sort_order' => $photoData['sort_order']]);
        }

        return response()->json([
            'message' => 'Photos reordered successfully',
        ]);
    }
}
