<?php

namespace App\Http\Controllers;

use App\Models\AssignedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssignedJobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AssignedJob  $assignedJob
     * @return \Illuminate\Http\Response
     */
    public function edit(AssignedJob $assignedJob)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssignedJob  $assignedJob
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssignedJob $assignedJob)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AssignedJob  $assignedJob
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssignedJob $assignedJob)
    {
        //
    }
}
