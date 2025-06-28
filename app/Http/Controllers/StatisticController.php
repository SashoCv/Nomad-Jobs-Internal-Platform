<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    public function statistics(Request $request)
    {
        // Filters: default to current year if not provided
        $dateFrom = $request->dateFrom ?? now()->startOfYear()->toDateString();
        $dateTo = $request->dateTo ?? now()->endOfYear()->toDateString();
        $companyId = $request->companyId;
        $agentId = $request->agentId;
        $country = $request->country;
        $contractType = $request->contractType;
        $status = $request->status;

        // Base query with necessary relationships
        $query = Candidate::query()
            ->with(['company:id,nameOfCompany', 'latestStatusHistory.status:id,nameOfStatus', 'agentCandidates'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Apply filters
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($agentId) {
            $query->whereHas('agentCandidates', function ($q) use ($agentId) {
                $q->where('user_id', $agentId);
            });
        }

        if ($country) {
            $query->where('country', $country);
        }

        if ($contractType) {
            $query->where('contractType', $contractType);
        }

        if ($status) {
            $query->whereHas('latestStatusHistory.status', function ($q) use ($status) {
                $q->where('nameOfStatus', $status);
            });
        }

        $candidates = $query->get();

        // --- Aggregations ---

        $statusCounts = $candidates->groupBy(fn($c) => optional(optional($c->latestStatusHistory)->status)->nameOfStatus ?? 'Unknown')
            ->map->count();


        $companyCounts = $candidates->groupBy(fn($c) => optional($c->company)->nameOfCompany ?? 'Unknown')
            ->map->count();

        $countryCounts = $candidates->groupBy(fn($c) => $c->country ?? 'Unknown')
            ->map->count();

        $typeCounts = $candidates->groupBy(fn($c) => $c->contractType ?? 'Unknown')
            ->map->count();

        $agentCounts = $candidates
            ->filter(fn($c) => optional($c->agentCandidates->first())->user_id)
            ->groupBy(fn($c) => $c->agentCandidates->first()->user_id)
            ->map->count();

        // Return structured data
        return response()->json([
            'statusCounts' => $statusCounts,
            'companyCounts' => $companyCounts,
            'countryCounts' => $countryCounts,
            'typeCounts' => $typeCounts,
            'agentCounts' => $agentCounts,
        ], 200, ['Content-Type' => 'application/json']);
    }
}
