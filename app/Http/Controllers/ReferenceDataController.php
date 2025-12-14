<?php

namespace App\Http\Controllers;

use App\Models\StatusArrival;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferenceDataController extends Controller
{
    /**
     * Get all status arrivals for reference data management
     */
    public function getStatusArrivals()
    {
        try {
            $statusArrivals = StatusArrival::orderBy('order_statuses')->orderBy('statusName')->get();
            return response()->json($statusArrivals);
        } catch (\Exception $e) {
            Log::error('Error retrieving status arrivals: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve status arrivals', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new status arrival
     */
    public function storeStatusArrival(Request $request)
    {
        try {
            $validated = $request->validate([
                'statusName' => 'required|string|max:255',
                'order_statuses' => 'nullable|integer',
            ]);

            $statusArrival = StatusArrival::create($validated);

            return response()->json([
                'message' => 'Status arrival created successfully',
                'statusArrival' => $statusArrival,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating status arrival: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create status arrival', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a status arrival
     */
    public function updateStatusArrival(Request $request, $id)
    {
        try {
            $statusArrival = StatusArrival::findOrFail($id);

            $validated = $request->validate([
                'statusName' => 'required|string|max:255',
                'order_statuses' => 'nullable|integer',
            ]);

            $statusArrival->update($validated);

            return response()->json([
                'message' => 'Status arrival updated successfully',
                'statusArrival' => $statusArrival,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating status arrival: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update status arrival', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a status arrival
     */
    public function deleteStatusArrival($id)
    {
        try {
            $statusArrival = StatusArrival::findOrFail($id);
            $statusArrival->delete();

            return response()->json([
                'message' => 'Status arrival deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting status arrival: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete status arrival', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all candidate statuses for reference data management
     */
    public function getCandidateStatuses()
    {
        try {
            $statuses = Status::orderBy('order')->orderBy('nameOfStatus')->get();
            return response()->json($statuses);
        } catch (\Exception $e) {
            Log::error('Error retrieving candidate statuses: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve candidate statuses', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new candidate status
     */
    public function storeCandidateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'nameOfStatus' => 'required|string|max:255',
                'order' => 'nullable|integer',
                'showOnHomePage' => 'nullable|boolean',
            ]);

            $status = Status::create($validated);

            return response()->json([
                'message' => 'Candidate status created successfully',
                'status' => $status,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create candidate status', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a candidate status
     */
    public function updateCandidateStatus(Request $request, $id)
    {
        try {
            $status = Status::findOrFail($id);

            $validated = $request->validate([
                'nameOfStatus' => 'required|string|max:255',
                'order' => 'nullable|integer',
                'showOnHomePage' => 'nullable|boolean',
            ]);

            $status->update($validated);

            return response()->json([
                'message' => 'Candidate status updated successfully',
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update candidate status', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a candidate status
     */
    public function deleteCandidateStatus($id)
    {
        try {
            $status = Status::findOrFail($id);
            $status->delete();

            return response()->json([
                'message' => 'Candidate status deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete candidate status', 'message' => $e->getMessage()], 500);
        }
    }
}
