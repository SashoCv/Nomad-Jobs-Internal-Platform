<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\AssignedJob;
use App\Models\CandidateContract;
use App\Models\CompanyJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssignedJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAgents()
    {
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'message' => 'You are not authorized to perform this action'
                ]);
            }
            $agents = User::where('role_id', 4)->get(['id', 'name', 'email']);
            return response()->json(['agents' => $agents], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to get agents'], 500);
        }
    }


    public function getAssignedJobs()
    {
        try {
            if (Auth::user()->role_id === 1 || Auth::user()->role_id === 2) {
                $assignedJobs = AssignedJob::with('user', 'companyJob')->get();
            } else if (Auth::user()->role_id === 4) {
                $assignedJobs = AssignedJob::with('user', 'companyJob')->where('user_id', Auth::user()->id)->get();
            }
            return response()->json(['assignedJobs' => $assignedJobs], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get assigned jobs'], 500);
        }
    }


    public function getAssignedJobsForAgent()
    {
        try {
            $assignedJobs = AssignedJob::with('user', 'companyJob')->where('user_id', Auth::user()->id)->get();

            if (count($assignedJobs) === 0) {
                return response()->json(['message' => 'No assigned jobs found'], 404);
            }

            return response()->json(['assignedJobs' => $assignedJobs], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to get assigned jobs'], 500);
        }
    }


    public function deleteAssignedJob($id)
    {
        try {
            if (Auth::user()->role_id != 1 || Auth::user()->role_id != 2) {
                return response()->json(['message' => 'You are not authorized to perform this action'], 401);
            }
            $assignedJob = AssignedJob::find($id);
            $assignedJob->delete();
            return response()->json(['message' => 'Assigned job deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to delete assigned job'], 500);
        }
    }

    public function removeAgentFromJob(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'company_job_id' => 'required|integer|exists:company_jobs,id',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $companyJobId = $request->company_job_id;
            $userId = $request->user_id;

            // Find and delete the assignment
            $assignment = AssignedJob::where('company_job_id', $companyJobId)
                ->where('user_id', $userId)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'message' => 'Assignment not found',
                ], 404);
            }

            $assignment->delete();

            return response()->json([
                'message' => 'Agent removed from job successfully',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Remove agent from job failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to remove agent'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $assignedJob = new AssignedJob();
            $assignedJob->user_id = $request->user_id;
            $assignedJob->company_job_id = $request->company_job_id;

            if ($assignedJob->save()) {
                return response()->json(['message' => 'Job assigned successfully'], 200);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Job assigned failed'], 500);
        }
    }

    public function bulkAssign(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'company_job_ids' => 'required|array',
                'company_job_ids.*' => 'integer|exists:company_jobs,id',
            ]);

            $userId = $request->user_id;
            $companyJobIds = $request->company_job_ids;

            // Get existing assignments for this user to avoid duplicates
            $existingAssignments = AssignedJob::where('user_id', $userId)
                ->whereIn('company_job_id', $companyJobIds)
                ->pluck('company_job_id')
                ->toArray();

            // Filter out jobs that are already assigned to this user
            $newJobIds = array_diff($companyJobIds, $existingAssignments);

            if (empty($newJobIds)) {
                return response()->json([
                    'message' => 'All selected jobs are already assigned to this agent',
                    'assigned_count' => 0,
                    'skipped_count' => count($existingAssignments),
                ], 200);
            }

            // Bulk insert new assignments
            $assignmentsData = array_map(function ($jobId) use ($userId) {
                return [
                    'user_id' => $userId,
                    'company_job_id' => $jobId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $newJobIds);

            AssignedJob::insert($assignmentsData);

            return response()->json([
                'message' => 'Jobs assigned successfully',
                'assigned_count' => count($newJobIds),
                'skipped_count' => count($existingAssignments),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk assign failed: ' . $e->getMessage());
            return response()->json(['message' => 'Bulk assignment failed'], 500);
        }
    }

    public function assignMultipleAgentsToJob(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'company_job_id' => 'required|integer|exists:company_jobs,id',
                'user_ids' => 'required|array',
                'user_ids.*' => 'integer|exists:users,id',
            ]);

            $companyJobId = $request->company_job_id;
            $userIds = $request->user_ids;

            // Get existing assignments for this job to avoid duplicates
            $existingAssignments = AssignedJob::where('company_job_id', $companyJobId)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            // Filter out agents that are already assigned to this job
            $newUserIds = array_diff($userIds, $existingAssignments);

            if (empty($newUserIds)) {
                return response()->json([
                    'message' => 'All selected agents are already assigned to this job',
                    'assigned_count' => 0,
                    'skipped_count' => count($existingAssignments),
                ], 200);
            }

            // Bulk insert new assignments
            $assignmentsData = array_map(function ($userId) use ($companyJobId) {
                return [
                    'user_id' => $userId,
                    'company_job_id' => $companyJobId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $newUserIds);

            AssignedJob::insert($assignmentsData);

            return response()->json([
                'message' => 'Agents assigned successfully',
                'assigned_count' => count($newUserIds),
                'skipped_count' => count($existingAssignments),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Assign multiple agents failed: ' . $e->getMessage());
            return response()->json(['message' => 'Assignment failed'], 500);
        }
    }

    public function assignToAnotherJobPosting(Request $request)
    {
        try {
            $companyJobId = $request->company_job_id;
            $agentCandidateId = $request->agent_candidate_id;
            $user = Auth::user();

            $agentCandidate = AgentCandidate::findOrFail($agentCandidateId);

            // Only candidates with status "Резерва" (5) or "Отказан" (6) can be reassigned
            $reassignableStatuses = [5, 6];
            if (!in_array($agentCandidate->status_for_candidate_from_agent_id, $reassignableStatuses)) {
                return response()->json(['message' => 'You can only reassign candidates with status Резерва or Отказан'], 403);
            }

            // Agents can only reassign their own candidates
            if ($user->hasRole(Role::AGENT)) {
                if ($agentCandidate->user_id !== $user->id) {
                    return response()->json(['message' => 'You can only reassign your own candidates'], 403);
                }
            }

            $candidateId = $agentCandidate->candidate_id;
            $originalAgentId = $agentCandidate->user_id;
            $oldContractId = $agentCandidate->contract_id;

            // Get new job posting defaults for the contract
            $newJob = CompanyJob::findOrFail($companyJobId);

            // Deactivate all active contracts for this candidate
            CandidateContract::where('candidate_id', $candidateId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Determine next contract_period_number (unique constraint on candidate_id + contract_period_number)
            $nextPeriodNumber = CandidateContract::withTrashed()
                ->where('candidate_id', $candidateId)
                ->max('contract_period_number') + 1;

            // Create a new contract based on the new job posting defaults
            $newContract = CandidateContract::create([
                'candidate_id' => $candidateId,
                'contract_period_number' => $nextPeriodNumber,
                'is_active' => true,
                'company_id' => $newJob->company_id,
                'position_id' => $newJob->position_id,
                'type_id' => 3,
                'contract_type' => $newJob->contract_type ?? '',
                'contract_type_id' => $newJob->contract_type_id,
                'salary' => $newJob->salary,
                'working_time' => $newJob->workTime,
                'agent_id' => $originalAgentId,
                'added_by' => $user->id,
                'date' => now(),
            ]);

            $agentCandidate->delete();

            $assignedJob = new AgentCandidate();
            $assignedJob->user_id = $originalAgentId;
            $assignedJob->company_job_id = $companyJobId;
            $assignedJob->status_for_candidate_from_agent_id = 1;
            $assignedJob->candidate_id = $candidateId;
            $assignedJob->contract_id = $newContract->id;

            if ($assignedJob->save()) {
                return response()->json(['message' => 'Job assigned successfully'], 200);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Job assignment failed'], 500);
        }
    }
}
