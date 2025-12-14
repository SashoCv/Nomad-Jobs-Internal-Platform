<?php

namespace App\Http\Controllers;

use App\Models\StatusForCandidateFromAgent;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferenceDataController extends Controller
{
    /**
     * Get all agent candidate statuses for reference data management
     */
    public function getAgentCandidateStatuses()
    {
        try {
            $statuses = StatusForCandidateFromAgent::orderBy('name')->get();
            return response()->json($statuses);
        } catch (\Exception $e) {
            Log::error('Error retrieving agent candidate statuses: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve agent candidate statuses', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new agent candidate status
     */
    public function storeAgentCandidateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $status = StatusForCandidateFromAgent::create($validated);

            return response()->json([
                'message' => 'Agent candidate status created successfully',
                'status' => $status,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating agent candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create agent candidate status', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an agent candidate status
     */
    public function updateAgentCandidateStatus(Request $request, $id)
    {
        try {
            $status = StatusForCandidateFromAgent::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $status->update($validated);

            return response()->json([
                'message' => 'Agent candidate status updated successfully',
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating agent candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update agent candidate status', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an agent candidate status
     */
    public function deleteAgentCandidateStatus($id)
    {
        try {
            $status = StatusForCandidateFromAgent::findOrFail($id);
            $status->delete();

            return response()->json([
                'message' => 'Agent candidate status deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting agent candidate status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete agent candidate status', 'message' => $e->getMessage()], 500);
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
