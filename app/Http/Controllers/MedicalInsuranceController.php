<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Candidate;
use App\Models\MedicalInsurance;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalInsuranceController extends Controller
{
    private function withCandidateRelations()
    {
        return [
            'candidate' => function ($query) {
                $query->select('id', 'fullNameCyrillic as fullName', 'contractType', 'company_id', 'position_id');
            },
            'candidate.company' => function ($query) {
                $query->select('id', 'nameOfCompany');
            },
            'candidate.position' => function ($query) {
                $query->select('id', 'jobPosition');
            }
        ];
    }

    public function index($id)
    {
        try {
            $medicalInsurances = MedicalInsurance::select('id', 'name', 'description', 'dateFrom', 'dateTo', 'candidate_id')
                ->with($this->withCandidateRelations())
                ->where('candidate_id', $id)
                ->get()
                ->map(function ($insurance) {
                    $insurance->dateFrom = Carbon::parse($insurance->dateFrom)->toISOString();
                    $insurance->dateTo = Carbon::parse($insurance->dateTo)->toISOString();
                    return $insurance;
                });

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurances
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
        }
    }

    public function showForCandidate($id)
    {
        try {
            $medicalInsurance = MedicalInsurance::select('id', 'name', 'description', 'dateFrom', 'dateTo', 'candidate_id')
                ->with($this->withCandidateRelations())
                ->where('id', $id)
                ->first();

            if ($medicalInsurance) {
                $medicalInsurance->dateFrom = Carbon::parse($medicalInsurance->dateFrom)->toISOString();
                $medicalInsurance->dateTo = Carbon::parse($medicalInsurance->dateTo)->toISOString();
            }


            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurance
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $medicalInsurance = MedicalInsurance::create($request->only('name', 'description', 'candidate_id', 'dateFrom', 'dateTo'));

            // Create calendar event for insurance expiry
            if ($request->dateTo) {
                $candidate = Candidate::find($request->candidate_id);
                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_INSURANCE_EXPIRY,
                        'candidate_id' => $request->candidate_id,
                    ],
                    [
                        'title' => 'Изтичаща застраховка',
                        'date' => Carbon::parse($request->dateTo)->format('Y-m-d'),
                        'company_id' => $candidate?->company_id,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Medical Insurance created successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance creation failed'
            ]);
        }
    }

    public function show()
    {
        try {
            $currentDate = now();
            $thirtyDaysAgo = $currentDate->copy()->addDays(30);

            // Exclude candidates with terminated/refused statuses
            $excludedStatuses = [
                Status::TERMINATED_CONTRACT,
                Status::REFUSED_MIGRATION,
                Status::REFUSED_CANDIDATE,
                Status::REFUSED_EMPLOYER,
                Status::REFUSED_BY_MIGRATION_OFFICE,
            ];

            $medicalInsurances = MedicalInsurance::select('id', 'name', 'description', 'dateFrom', 'dateTo', 'candidate_id')
                ->with($this->withCandidateRelations())
                ->whereHas('candidate', function ($query) use ($excludedStatuses) {
                    $query->whereNotIn('status_id', $excludedStatuses);
                })
                ->where('dateTo', '<=', $thirtyDaysAgo)
                ->orderBy('dateTo', 'desc')
                ->paginate();

            $medicalInsurances->getCollection()->transform(function ($insurance) {
                $insurance->dateFrom = \Carbon\Carbon::createFromFormat('Y-m-d', $insurance->dateFrom)->toISOString();
                $insurance->dateTo = \Carbon\Carbon::createFromFormat('Y-m-d', $insurance->dateTo)->toISOString();
                return $insurance;
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $medicalInsurances
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance fetch failed'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $medicalInsurance = MedicalInsurance::findOrFail($id);
            $medicalInsurance->update($request->only('name', 'description', 'candidate_id', 'dateFrom', 'dateTo'));

            // Update calendar event for insurance expiry
            if ($request->dateTo) {
                $candidate = Candidate::find($medicalInsurance->candidate_id);
                CalendarEvent::updateOrCreate(
                    [
                        'type' => CalendarEvent::TYPE_INSURANCE_EXPIRY,
                        'candidate_id' => $medicalInsurance->candidate_id,
                    ],
                    [
                        'title' => 'Изтичаща застраховка',
                        'date' => Carbon::parse($request->dateTo)->format('Y-m-d'),
                        'company_id' => $candidate?->company_id,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Medical Insurance updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance update failed'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $medicalInsurance = MedicalInsurance::findOrFail($id);
            $candidateId = $medicalInsurance->candidate_id;
            $medicalInsurance->delete();

            // Delete the calendar event for this insurance
            CalendarEvent::where('type', CalendarEvent::TYPE_INSURANCE_EXPIRY)
                ->where('candidate_id', $candidateId)
                ->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Medical Insurance deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Medical Insurance deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Medical Insurance deletion failed'
            ]);
        }
    }
}
