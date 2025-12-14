<?php

namespace App\Http\Controllers;

use App\Models\StatusArrival;
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
}
