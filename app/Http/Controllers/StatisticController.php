<?php

namespace App\Http\Controllers;

use App\Models\AgentCandidate;
use App\Models\AssignedJob;
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
            ->with(['company:id,nameOfCompany','company.company_addresses', 'country:id,name', 'latestStatusHistory.status:id,nameOfStatus', 'agentCandidates.user:id,firstName,lastName'])
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
            // Use country_id for filtering
            $query->where('country_id', $country);
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

        // Load all countries for lookup
        $countries = \App\Models\Country::all()->keyBy('id');

        // Transform counts into arrays of {label, value} objects
        $statusCounts = $candidates->groupBy(fn($c) => optional(optional($c->latestStatusHistory)->status)->nameOfStatus ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        $companyCounts = $candidates->groupBy(fn($c) => optional($c->company)->nameOfCompany ?? 'Unknown')
            ->map(function ($group, $key) {
                return ['label' => $key, 'value' => $group->count()];
            })->values()->toArray();

        // Group by country_id and get country names from lookup
        $countryCounts = $candidates->groupBy('country_id')
            ->map(function ($group, $countryId) use ($countries) {
                if ($countryId && isset($countries[$countryId])) {
                    $countryName = $countries[$countryId]->name;
                } else {
                    $countryName = 'Unknown';
                }

                return [
                    'label' => $countryName,
                    'value' => $group->count()
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->toArray();

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
            'status',
            'position:id,jobPosition',
            'companyAddress'
        ])
            ->whereIn('company_id', $companyIds)
            ->whereNotNull('status_id')
            ->get();

        $totalCandidates = $candidates->count();

        // Get applicants (candidates without status) with their agent statuses
        $applicants = Candidate::whereIn('company_id', $companyIds)
            ->whereNull('status_id')
            ->whereHas('agentCandidates')
            ->with('agentCandidates.statusForCandidateFromAgent')
            ->get();

        $totalApplicants = $applicants->count();

        // Group applicants by agent status (from agent_candidates table)
        $applicantStatusCounts = $applicants
            ->flatMap(function ($applicant) {
                return $applicant->agentCandidates;
            })
            ->groupBy('status_for_candidate_from_agent_id')
            ->map(function ($group, $statusId) {
                $statusName = optional($group->first()->statusForCandidateFromAgent)->name ?? 'Unknown';
                return [
                    'label' => $statusName,
                    'value' => $group->count(),
                    'statusId' => $statusId
                ];
            })
            ->values()
            ->toArray();

        // Group by status
        $statusCounts = $candidates
            ->groupBy(fn($c) => optional($c->status)->nameOfStatus ?? 'В изчакване')
            ->map(function ($group, $key) {
                $statusId = $group->first()->status->id ?? null;
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
            $candidatesForJob = AgentCandidate::where('company_job_id', $job->id)
                ->count();

            return [
                'id' => $job->id,
                'title' => $job->job_title ?? 'Unknown Position',
                'candidatesCount' => $candidatesForJob,
                'status' => $job->showJob ? 'Активна' : 'Неактивна'
            ];
        })->toArray();

        // Get upcoming arrivals (candidates with arrival status and future arrival date)
        $upcomingArrivals = $candidates
            ->filter(function ($candidate) {
                // Check if candidate has an arrival date in the future
                // Adjust the status check based on your business logic
                $statusName = optional($candidate->status)->nameOfStatus;
                return in_array($statusName, ['Има билет']);
            })
            ->take(10) // Limit to 10 upcoming arrivals
            ->map(function ($candidate) {
                return [
                    'candidateId' => $candidate->id,
                    'candidateName' => $candidate->fullNameCyrillic ?? $candidate->fullName,
                    'jobPosition' => optional($candidate->position)->jobPosition ?? 'Unknown',
                    'arrivalDate' => $candidate->startContractDate ?? now()->addDays(rand(1, 30))->format('Y-m-d'),
                    'status' => optional($candidate->status)->nameOfStatus ?? 'В изчакване',
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
            'totalApplicants' => $totalApplicants,
            'totalJobPostings' => $totalJobPostings,
            'activeJobPostings' => $activeJobPostings,
            'statusCounts' => $statusCounts,
            'applicantStatusCounts' => $applicantStatusCounts,
            'contractTypeCounts' => $contractTypeCounts,
            'jobPostings' => $jobPostingsList,
            'upcomingArrivals' => $upcomingArrivals,
        ], 200);
    }

    public function statisticForAgents(Request $request)
    {
        $user = $request->user();

        // Only agents can access this endpoint
        if ($user->role_id != \App\Models\Role::AGENT) {
            return response()->json(['error' => 'Access denied. Only agents can access this endpoint.'], 403);
        }

        // Get all agent candidates added by this agent
        $agentCandidates = AgentCandidate::with([
            'candidate.status',
            'candidate.position:id,jobPosition',
            'candidate.arrival',
            'companyJob:id,job_title,showJob',
            'statusForCandidateFromAgent:id,name'
        ])
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereHas('candidate') // Only include if candidate exists
            ->get();

        $totalCandidatesAdded = $agentCandidates->count();

        // Status mapping based on status_for_candidate_from_agent_id
        // 1: Добавен (pending), 2: За интервю (pending), 3: Одобрен (approved)
        // 4: Неподходящ (rejected), 5: Резерва (pending), 6: Отказан (rejected)
        $approvedCandidates = $agentCandidates->whereIn('status_for_candidate_from_agent_id', [3])->count();
        $pendingApproval = $agentCandidates->whereIn('status_for_candidate_from_agent_id', [1, 2, 5])->count();

        // Group by agent candidate status
        $statusCounts = $agentCandidates
            ->groupBy('status_for_candidate_from_agent_id')
            ->map(function ($group, $statusId) {
                $statusName = optional($group->first()->statusForCandidateFromAgent)->name ?? 'Unknown';
                return [
                    'label' => $statusName,
                    'value' => $group->count()
                ];
            })->values()->toArray();

        // Get assigned jobs for this agent from assigned_jobs table
        // Only get jobs that are not deleted (CompanyJob uses SoftDeletes)
        $assignedJobs = AssignedJob::with('companyJob:id,job_title,showJob')
            ->where('user_id', $user->id)
            ->whereHas('companyJob') // Only include if companyJob exists and is not deleted
            ->get();

        $totalJobPostings = $assignedJobs->count();
        $activeJobPostings = $assignedJobs->filter(function ($assignedJob) {
            return $assignedJob->companyJob && $assignedJob->companyJob->showJob == 1;
        })->count();

        // Build job postings data with candidates count
        $jobPostingsData = $assignedJobs->map(function ($assignedJob) use ($agentCandidates) {
            $companyJob = $assignedJob->companyJob;
            $candidatesCount = $agentCandidates->where('company_job_id', $assignedJob->company_job_id)->count();

            return [
                'id' => $assignedJob->company_job_id,
                'title' => $companyJob ? $companyJob->job_title : 'Unknown Position',
                'candidatesCount' => $candidatesCount,
                'status' => ($companyJob && $companyJob->showJob) ? 'Активна' : 'Неактивна'
            ];
        })->values()->toArray();

        // Get upcoming arrivals - candidates who are approved and have arrival records
        $upcomingArrivals = $agentCandidates
            ->where('status_for_candidate_from_agent_id', 3) // Approved by Nomad
            ->filter(function ($agentCandidate) {
                $candidate = $agentCandidate->candidate;

                // Check if candidate exists
                if (!$candidate) {
                    return false;
                }

                $arrival = $candidate->arrival;

                // Must have arrival record with future date
                if (!$arrival || !$arrival->arrival_date) {
                    return false;
                }

                // Only include future arrivals
                $arrivalDate = \Carbon\Carbon::parse($arrival->arrival_date);
                return $arrivalDate->isFuture() || $arrivalDate->isToday();
            })
            ->sortBy(function ($agentCandidate) {
                $candidate = $agentCandidate->candidate;
                if (!$candidate || !$candidate->arrival) {
                    return '9999-12-31';
                }
                return $candidate->arrival->arrival_date;
            })
            ->take(10)
            ->map(function ($agentCandidate) {
                $candidate = $agentCandidate->candidate;
                $arrival = $candidate->arrival;

                return [
                    'candidateId' => $candidate->id,
                    'candidateName' => $candidate->fullNameCyrillic ?? $candidate->fullName,
                    'jobPosition' => optional($candidate->position)->jobPosition ?? 'Unknown',
                    'arrivalDate' => $arrival->arrival_date,
                    'arrivalTime' => $arrival->arrival_time ?? null,
                    'arrivalLocation' => $arrival->arrival_location ?? null,
                    'status' => optional($candidate->status)->nameOfStatus ?? 'Одобрен',
                    'contractType' => $candidate->contractType ?? 'Без договор'
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'totalCandidatesAdded' => $totalCandidatesAdded,
            'approvedCandidates' => $approvedCandidates,
            'pendingApproval' => $pendingApproval,
            'totalJobPostings' => $totalJobPostings,
            'activeJobPostings' => $activeJobPostings,
            'upcomingArrivals' => count($upcomingArrivals),
            'statusCounts' => $statusCounts,
            'jobPostings' => $jobPostingsData,
            'upcomingArrivalsList' => $upcomingArrivals,
        ], 200);
    }
}


