<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    /**
     * Store a newly created experience record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'company_name' => 'nullable|string',
            'position' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
        ]);

        $experience = new Experience();
        $experience->candidate_id = $validated['candidate_id'];
        $experience->company_name = $validated['company_name'] ?? null;
        $experience->position = $validated['position'] ?? null;
        $experience->responsibilities = $validated['responsibilities'] ?? null;
        $experience->start_date = $validated['start_date'] ?? null;
        $experience->end_date = $validated['end_date'] ?? null;
        $experience->save();

        return response()->json([
            'success' => true,
            'data' => $experience,
            'message' => 'Experience created successfully',
        ], 201);
    }

    /**
     * Update the specified experience record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $experience = Experience::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'nullable|string',
            'position' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
        ]);

        $experience->company_name = $validated['company_name'] ?? $experience->company_name;
        $experience->position = $validated['position'] ?? $experience->position;
        $experience->responsibilities = $validated['responsibilities'] ?? $experience->responsibilities;
        $experience->start_date = $validated['start_date'] ?? $experience->start_date;
        $experience->end_date = $validated['end_date'] ?? $experience->end_date;
        $experience->save();

        return response()->json([
            'success' => true,
            'data' => $experience,
            'message' => 'Experience updated successfully',
        ]);
    }

    /**
     * Remove the specified experience record.
     */
    public function destroy($id): JsonResponse
    {
        $experience = Experience::findOrFail($id);
        $experience->delete();

        return response()->json([
            'success' => true,
            'message' => 'Experience deleted successfully',
        ]);
    }
}
