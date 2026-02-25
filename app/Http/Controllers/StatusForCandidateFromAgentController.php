<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\CompanyJob;
use App\Models\Education;
use App\Models\Status;
use App\Models\StatusForCandidateFromAgent;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatusForCandidateFromAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $statusForCandidateFromAgent = StatusForCandidateFromAgent::all(['id', 'name']);
            return response()->json($statusForCandidateFromAgent);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function show(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function edit(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $candidateFromAgent = AgentCandidate::where('candidate_id', $id)->first();

            if (!$candidateFromAgent) {
                return response()->json(['message' => 'Candidate not found'], 404);
            }

            $previousStatusId = $candidateFromAgent->status_for_candidate_from_agent_id;

            DB::transaction(function () use ($request, $id, $candidateFromAgent) {
                $candidateFromAgent->status_for_candidate_from_agent_id = $request->status_for_candidate_from_agent_id;

                // Save status_date if provided
                if ($request->has('status_date')) {
                    $candidateFromAgent->status_date = $request->status_date;
                }

                if (in_array($request->status_for_candidate_from_agent_id, [StatusForCandidateFromAgent::APPROVED, StatusForCandidateFromAgent::UNSUITABLE, StatusForCandidateFromAgent::RESERVE])) {
                    $updateTypeOfCandidate = Candidate::where('id', $id)->first();
                    $updateTypeOfCandidate->type_id = Candidate::TYPE_CANDIDATE;

                    $education = Education::where('candidate_id', $id)->first();

                    if ($education) {
                        $educationFields = [
                            $education->school_name ?? "",
                            $education->degree ?? "",
                            $education->field_of_study ?? "",
                            $education->start_date ?? "",
                            $education->end_date ?? ""
                        ];

                        $updateTypeOfCandidate->education = implode("-", array_filter($educationFields, fn($value) => !empty($value)));
                    } else {
                        $updateTypeOfCandidate->education = null;
                    }

                    // Candidate model auto-syncs type_id to active contract
                    $updateTypeOfCandidate->save();
                }

                $candidateFromAgent->save();

                // Create calendar event for interview
                if ($request->status_for_candidate_from_agent_id == StatusForCandidateFromAgent::FOR_INTERVIEW && $request->has('status_date')) {
                    $statusDateTime = Carbon::parse($request->status_date);
                    $companyJob = $candidateFromAgent->companyJob;

                    CalendarEvent::updateOrCreate(
                        [
                            'type' => CalendarEvent::TYPE_INTERVIEW,
                            'candidate_id' => $id,
                        ],
                        [
                            'title' => 'Интервю',
                            'date' => $statusDateTime->toDateString(),
                            'time' => $statusDateTime->toTimeString(),
                            'company_id' => $companyJob?->company_id,
                            'created_by' => Auth::id(),
                        ]
                    );
                }
            });

            // Update job filled status outside transaction (non-critical)
            if ($candidateFromAgent->company_job_id) {
                $this->checkAndUpdateJobFilledStatus($candidateFromAgent->company_job_id);
            }

            return response()->json(['message' => 'Status updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StatusForCandidateFromAgent  $statusForCandidateFromAgent
     * @return \Illuminate\Http\Response
     */
    public function destroy(StatusForCandidateFromAgent $statusForCandidateFromAgent)
    {
        //
    }

    /**
     * Check if a job posting should be marked as "filled" based on approved candidates count.
     *
     * @param int $companyJobId
     * @return void
     */
    private function checkAndUpdateJobFilledStatus(int $companyJobId): void
    {
        $companyJob = CompanyJob::find($companyJobId);

        if (!$companyJob) {
            return;
        }

        // Count approved candidates
        $approvedCount = AgentCandidate::where('company_job_id', $companyJobId)
            ->where('status_for_candidate_from_agent_id', StatusForCandidateFromAgent::APPROVED)
            ->whereNull('deleted_at')
            ->count();

        if ($approvedCount >= $companyJob->number_of_positions && in_array($companyJob->status, ['active', 'inactive'])) {
            $companyJob->status = 'filled';
            $companyJob->save();
        } elseif ($approvedCount < $companyJob->number_of_positions && $companyJob->status === 'filled') {
            $companyJob->status = 'active';
            $companyJob->save();
        }
    }
}
