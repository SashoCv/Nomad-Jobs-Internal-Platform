<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
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


    public function statisticForCompanies(Request $request)
    {
        $user = $request->user();

        // Determine company IDs based on user role
        $companyIds = [];

        // If user is Company Owner, get all companies they own
        if ($user->role_id == \App\Models\Role::COMPANY_OWNER) {
            $companyOwner = \App\Models\UserOwner::where('user_id', $user->id)->get();
            $companyIds = $companyOwner->pluck('company_id')->toArray();
        }
        // If regular company user, get their single company
        elseif ($user->company_id) {
            $companyIds = [$user->company_id];
        }

        if (empty($companyIds)) {
            return response()->json(['error' => 'User is not associated with any company'], 403);
        }

        // Get all companies info
        $companies = \App\Models\Company::with('serviceContracts')
            ->whereIn('id', $companyIds)
            ->get();

        if ($companies->isEmpty()) {
            return response()->json(['error' => 'No companies found'], 404);
        }

        // For single company user or primary company for owner
        $primaryCompany = $companies->first();

        // Check if any company has active contract
        $hasActiveContract = false;
        $contractExpiryDate = null;
        $companiesWithContracts = [];

        foreach ($companies as $comp) {
            if ($comp->serviceContracts) {
                $activeContract = $comp->serviceContracts
                    ->where('endDate', '>=', now())
                    ->sortByDesc('endDate')
                    ->first();

                if ($activeContract) {
                    $hasActiveContract = true;
                    $companiesWithContracts[] = [
                        'company' => $comp->nameOfCompany,
                        'expiryDate' => \Carbon\Carbon::parse($activeContract->endDate)->format('d.m.Y')
                    ];

                    // Set the latest expiry date
                    if (!$contractExpiryDate || $activeContract->endDate > $contractExpiryDate) {
                        $contractExpiryDate = $activeContract->endDate;
                    }
                }
            }
        }

        // Get all candidates for these companies
        $candidates = Candidate::with([
            'latestStatusHistory.status:id,nameOfStatus',
            'position:id,jobPosition',
            'companyAddress'
        ])
            ->whereIn('company_id', $companyIds)
            ->get();

        $totalCandidates = $candidates->count();

        // Group by status
        $statusCounts = $candidates
            ->groupBy(fn($c) => optional(optional($c->latestStatusHistory)->status)->nameOfStatus ?? 'В изчакване')
            ->map(function ($group, $key) {
                $statusId = $group->first()->latestStatusHistory->status->id ?? null;
                return [
                    'label' => $key,
                    'value' => $group->count(),
                    'statusId' => $statusId
                ];
            })->values()->toArray();

        // Group by contract type
        $contractTypeCounts = $candidates
            ->groupBy(fn($c) => $c->contractType ?? 'Без договор')
            ->map(function ($group, $key) {
                return [
                    'label' => $key,
                    'value' => $group->count(),
                    'contractType' => $key
                ];
            })->values()->toArray();

        // Get job postings for these companies
        $jobPostings = \App\Models\CompanyJob::whereIn('company_id', $companyIds)
            ->with('position:id,jobPosition')
            ->get();

        $totalJobPostings = $jobPostings->count();
        $activeJobPostings = $jobPostings->where('showJob', 1)->count();

        $jobPostingsList = $jobPostings->map(function ($job) use ($candidates) {
            $candidatesForJob = AgentCandidate::whereIn('candidate_id', $candidates->pluck('id'))
                ->where('company_job_id', $job->id)
                ->count();

            return [
                'id' => $job->id,
                'title' => $job->position->jobPosition ?? 'Unknown Position',
                'candidatesCount' => $candidatesForJob,
                'status' => $job->showJob ? 'Активна' : 'Неактивна'
            ];
        })->toArray();

        // Get upcoming arrivals (candidates with arrival status and future arrival date)
        $upcomingArrivals = $candidates
            ->filter(function ($candidate) {
                // Check if candidate has an arrival date in the future
                // Adjust the status check based on your business logic
                $statusName = optional(optional($candidate->latestStatusHistory)->status)->nameOfStatus;
                return in_array($statusName, ['Одобрен', 'В процес', 'Очаква документи']);
            })
            ->take(10) // Limit to 10 upcoming arrivals
            ->map(function ($candidate) {
                return [
                    'candidateId' => $candidate->id,
                    'candidateName' => $candidate->fullNameCyrillic ?? $candidate->fullName,
                    'jobPosition' => optional($candidate->position)->jobPosition ?? 'Unknown',
                    'arrivalDate' => $candidate->startContractDate ?? now()->addDays(rand(1, 30))->format('Y-m-d'),
                    'status' => optional(optional($candidate->latestStatusHistory)->status)->nameOfStatus ?? 'В изчакване',
                    'contractType' => $candidate->contractType ?? 'Без договор'
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'company' => [
                'id' => $primaryCompany->id,
                'name' => $primaryCompany->nameOfCompany,
                'logo' => $primaryCompany->logoPath ? url('storage/' . $primaryCompany->logoPath) : null,
                'hasActiveContract' => $hasActiveContract,
                'contractExpiryDate' => $contractExpiryDate ? \Carbon\Carbon::parse($contractExpiryDate)->format('d.m.Y') : null,
            ],
            'totalCompanies' => count($companyIds),
            'companiesWithContracts' => $companiesWithContracts,
            'allCompanies' => $companies->map(function ($comp) {
                return [
                    'id' => $comp->id,
                    'name' => $comp->nameOfCompany,
                    'logo' => $comp->logoPath ? url('storage/' . $comp->logoPath) : null,
                ];
            })->toArray(),
            'totalCandidates' => $totalCandidates,
            'totalJobPostings' => $totalJobPostings,
            'activeJobPostings' => $activeJobPostings,
            'statusCounts' => $statusCounts,
            'contractTypeCounts' => $contractTypeCounts,
            'jobPostings' => $jobPostingsList,
            'upcomingArrivals' => $upcomingArrivals,
        ], 200);
    }
}


