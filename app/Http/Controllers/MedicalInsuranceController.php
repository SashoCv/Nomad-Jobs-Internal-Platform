<?php

namespace App\Http\Controllers;

use App\Models\MedicalInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalInsuranceController extends Controller
{
    private function withCandidateRelations()
    {
        return [
            'candidate' => function ($query) {
                $query->select('id', 'fullName', 'company_id', 'position_id');
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
                ->get();

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

            $medicalInsurances = MedicalInsurance::select('id', 'name', 'description', 'dateFrom', 'dateTo', 'candidate_id')
                ->with($this->withCandidateRelations())
                ->where('dateTo', '<=', $thirtyDaysAgo)
                ->paginate();

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
            $medicalInsurance->delete();

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