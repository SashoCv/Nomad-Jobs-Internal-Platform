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
        $cityId = $request->cityId;

        // Base query with necessary relationships
        $query = Candidate::query()
            ->with(['company:id,nameOfCompany','company.company_addresses', 'latestStatusHistory.status:id,nameOfStatus', 'agentCandidates.user:id,firstName,lastName'])
            ->whereHas('latestStatusHistory.status')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Apply filters
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($cityId) {
            $query->whereHas('companyAddress', function ($query) use ($cityId) {
                    $query->where('city_id', $cityId);
                });
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
            $map = [
                'ЕРПР 1' => 'ЕРПР 1',
                'ЕРПР 2' => 'ЕРПР 2',
                'ЕРПР 3' => 'ЕРПР 3',
                '90 дни' => '90 дни',
                '9 месеца' => '9 месеца',
            ];

            $contractTypeLatin = $map[$contractType] ?? $contractType;
            $query->where('contractType', $contractTypeLatin);
        }

        if ($status) {
            $query->whereHas('latestStatusHistory.status', function ($q) use ($status) {
                $q->where('nameOfStatus', $status);
            });
        }

        $candidates = $query->get();

        // Transform counts into arrays of {label, value} objects
        $statusCounts = $candidates->groupBy(fn($c) => optional(optional($c->latestStatusHistory)->status)->nameOfStatus ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        $companyCounts = $candidates->groupBy(fn($c) => optional($c->company)->nameOfCompany ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        $countryCounts = $candidates->groupBy(fn($c) => $c->country ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        $typeCounts = $candidates->groupBy(fn($c) => $c->contractType ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        $agentCounts = $candidates
            ->filter(fn($c) => optional($c->agentCandidates->first())->user_id)
            ->groupBy(function($c) {
                $agentCandidate = $c->agentCandidates->first();
                if ($agentCandidate && $agentCandidate->user) {
                    return $agentCandidate->user->firstName . ' ' . $agentCandidate->user->lastName;
                }
                return 'Unknown Agent';
            })
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

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
?>
