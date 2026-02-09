<?php

namespace App\Http\Controllers;

use App\Models\Education;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    /**
     * Store a newly created education record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'school_name' => 'nullable|string',
            'degree' => 'nullable|string',
            'field_of_study' => 'nullable|string',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
        ]);

        $education = new Education();
        $education->candidate_id = $validated['candidate_id'];
        $education->school_name = $validated['school_name'] ?? null;
        $education->degree = $validated['degree'] ?? null;
        $education->field_of_study = $validated['field_of_study'] ?? null;
        $education->start_date = $validated['start_date'] ?? null;
        $education->end_date = $validated['end_date'] ?? null;
        $education->save();

        return response()->json([
            'success' => true,
            'data' => $education,
            'message' => 'Education created successfully',
        ], 201);
    }

    /**
     * Update the specified education record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $education = Education::findOrFail($id);

        $validated = $request->validate([
            'school_name' => 'nullable|string',
            'degree' => 'nullable|string',
            'field_of_study' => 'nullable|string',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
        ]);

        $education->school_name = $validated['school_name'] ?? $education->school_name;
        $education->degree = $validated['degree'] ?? $education->degree;
        $education->field_of_study = $validated['field_of_study'] ?? $education->field_of_study;
        $education->start_date = $validated['start_date'] ?? $education->start_date;
        $education->end_date = $validated['end_date'] ?? $education->end_date;
        $education->save();

        return response()->json([
            'success' => true,
            'data' => $education,
            'message' => 'Education updated successfully',
        ]);
    }

    /**
     * Remove the specified education record.
     */
    public function destroy($id): JsonResponse
    {
        $education = Education::findOrFail($id);
        $education->delete();

        return response()->json([
            'success' => true,
            'message' => 'Education deleted successfully',
        ]);
    }
}
