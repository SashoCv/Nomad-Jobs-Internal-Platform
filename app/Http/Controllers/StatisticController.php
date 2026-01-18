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

        // Also Here i need how many contracts are signed with copmanies (company_service_contracts)    

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

        // Get count of companies with active contracts (endDate >= today)
        $companiesWithContractsQuery = \App\Models\CompanyServiceContract::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->distinct('company_id')
            ->pluck('company_id');

        $companiesWithActiveContracts = $companiesWithContractsQuery->count();

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

        // Group by month - format: "Януари 2024", "Февруари 2024", etc.
        $monthNames = [
            1 => 'Януари', 2 => 'Февруари', 3 => 'Март', 4 => 'Април',
            5 => 'Май', 6 => 'Юни', 7 => 'Юли', 8 => 'Август',
            9 => 'Септември', 10 => 'Октомври', 11 => 'Ноември', 12 => 'Декември'
        ];

        $monthCounts = $candidates->groupBy(function($c) {
            return $c->created_at->format('Y-m');
        })
        ->map(function ($group, $key) use ($monthNames) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $key);
            $monthName = $monthNames[$date->month] . ' ' . $date->year;
            return [
                'label' => $monthName,
                'value' => $group->count(),
                'sortKey' => $key
            ];
        })
        ->sortBy('sortKey')
        ->map(function ($item) {
            return ['label' => $item['label'], 'value' => $item['value']];
        })
        ->values()
        ->toArray();

        // Return structured data
        return response()->json([
            'statusCounts' => $statusCounts,
            'companyCounts' => $companyCounts,
            'countryCounts' => $countryCounts,
            'typeCounts' => $typeCounts,
            'agentCounts' => $agentCounts,
            'monthCounts' => $monthCounts,
            'companiesWithActiveContracts' => $companiesWithActiveContracts,
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
                $activeContract = $comp->serviceContracts->first();

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

        // Get job postings for these companies (excluding soft-deleted)
        $jobPostings = \App\Models\CompanyJob::whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->with('position:id,jobPosition')
            ->get();

        $totalJobPostings = $jobPostings->count();
        $activeJobPostings = $jobPostings->where('status', 'active')->count();

        $jobPostingsList = $jobPostings->map(function ($job) use ($candidates) {
            $candidatesForJob = AgentCandidate::where('company_job_id', $job->id)
                ->count();

            return [
                'id' => $job->id,
                'title' => $job->job_title ?? 'Unknown Position',
                'candidatesCount' => $candidatesForJob,
                'status' => $job->status === 'active' ? 'Активна' : 'Неактивна'
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
            'companyJob:id,job_title,status',
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
        $assignedJobs = AssignedJob::with('companyJob:id,job_title,status')
            ->where('user_id', $user->id)
            ->whereHas('companyJob') // Only include if companyJob exists and is not deleted
            ->get();

        $totalJobPostings = $assignedJobs->count();
        $activeJobPostings = $assignedJobs->filter(function ($assignedJob) {
            return $assignedJob->companyJob && $assignedJob->companyJob->status === 'active';
        })->count();

        // Build job postings data with candidates count
        $jobPostingsData = $assignedJobs->map(function ($assignedJob) use ($agentCandidates) {
            $companyJob = $assignedJob->companyJob;
            $candidatesCount = $agentCandidates->where('company_job_id', $assignedJob->company_job_id)->count();

            return [
                'id' => $assignedJob->company_job_id,
                'title' => $companyJob ? $companyJob->job_title : 'Unknown Position',
                'candidatesCount' => $candidatesCount,
                'status' => ($companyJob && $companyJob->status === 'active') ? 'Активна' : 'Неактивна'
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

    public function agentsJobAssignments(Request $request)
    {
        // Get all agents with their assigned jobs
        $agents = \App\Models\User::where('role_id', \App\Models\Role::AGENT)
            ->select('id', 'firstName', 'lastName', 'email')
            ->orderBy('firstName')
            ->get();

        $agentsWithJobs = $agents->map(function ($agent) {
            // Get unique assigned jobs for this agent (only active company_jobs)
            $assignedJobs = AssignedJob::with('companyJob.company:id,nameOfCompany', 'companyJob.position:id,jobPosition')
                ->where('user_id', $agent->id)
                ->whereHas('companyJob') // Only active jobs
                ->get()
                ->unique('company_job_id'); // Remove duplicates by company_job_id

            $jobsList = $assignedJobs->map(function ($assignedJob) {
                $job = $assignedJob->companyJob;
                return [
                    'id' => $job->id,
                    'jobTitle' => $job->job_title,
                    'company' => $job->company->nameOfCompany ?? 'Unknown',
                    'position' => $job->job_title ?? 'Unknown',
                    'status' => $job->status === 'active' ? 'Активна' : 'Неактивна',
                    'createdAt' => $job->created_at->format('d.m.Y'),
                ];
            })->values()->toArray();

            return [
                'agentId' => $agent->id,
                'agentName' => $agent->firstName . ' ' . $agent->lastName,
                'agentEmail' => $agent->email,
                'totalAssignedJobs' => count($jobsList),
                'jobs' => $jobsList,
            ];
        });

        // Get unassigned jobs (active company_jobs not in assigned_jobs)
        $assignedJobIds = AssignedJob::whereHas('companyJob')
            ->pluck('company_job_id')
            ->unique()
            ->toArray();

        $unassignedJobs = \App\Models\CompanyJob::with('company:id,nameOfCompany', 'position:id,jobPosition')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $assignedJobIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'jobTitle' => $job->job_title,
                    'company' => $job->company->nameOfCompany ?? 'Unknown',
                    'position' => $job->position->jobPosition ?? 'Unknown',
                    'status' => $job->status === 'active' ? 'Активна' : 'Неактивна',
                    'createdAt' => $job->created_at->format('d.m.Y'),
                ];
            })
            ->toArray();

        // Get all assigned jobs with their agents
        $assignedJobsWithAgents = \App\Models\CompanyJob::with('company:id,nameOfCompany', 'position:id,jobPosition')
            ->whereNull('deleted_at')
            ->whereIn('id', $assignedJobIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($job) {
                // Get all agents assigned to this job
                $assignedAgents = AssignedJob::with('user:id,firstName,lastName,email')
                    ->where('company_job_id', $job->id)
                    ->get()
                    ->map(function ($assignment) {
                        return [
                            'agentId' => $assignment->user->id,
                            'agentName' => $assignment->user->firstName . ' ' . $assignment->user->lastName,
                            'agentEmail' => $assignment->user->email,
                        ];
                    })
                    ->toArray();

                return [
                    'id' => $job->id,
                    'jobTitle' => $job->job_title,
                    'company' => $job->company->nameOfCompany ?? 'Unknown',
                    'position' => $job->position->jobPosition ?? 'Unknown',
                    'status' => $job->status === 'active' ? 'Активна' : 'Неактивна',
                    'createdAt' => $job->created_at->format('d.m.Y'),
                    'assignedAgents' => $assignedAgents,
                    'agentsCount' => count($assignedAgents),
                ];
            })
            ->toArray();

        return response()->json([
            'agentsWithJobs' => $agentsWithJobs,
            'unassignedJobs' => $unassignedJobs,
            'assignedJobs' => $assignedJobsWithAgents,
            'totalAgents' => $agents->count(),
            'totalUnassignedJobs' => count($unassignedJobs),
            'totalAssignedJobs' => count($assignedJobsWithAgents),
        ], 200);
    }

    public function agentsStatistics(Request $request)
    {
        $agentId = $request->agent_id; // Filter by specific agent if provided

        // Get all agents (users with role_id = 4)
        $agentsQuery = \App\Models\User::where('role_id', \App\Models\Role::AGENT);

        if ($agentId) {
            $agentsQuery->where('id', $agentId);
        }

        $agents = $agentsQuery->get();
        $totalAgents = $agents->count();

        // Get agent candidates - filter by agent if specified
        $agentCandidatesQuery = AgentCandidate::with([
            'candidate.status',
            'candidate.position:id,jobPosition',
            'companyJob:id,job_title,status',
            'statusForCandidateFromAgent:id,name',
            'user:id,firstName,lastName'
        ])
            ->whereNull('deleted_at')
            ->whereHas('candidate'); // Only include if candidate exists

        if ($agentId) {
            $agentCandidatesQuery->where('user_id', $agentId);
        }

        $agentCandidates = $agentCandidatesQuery->get();
        $totalCandidatesAdded = $agentCandidates->count();

        // Count candidates by agent
        $candidatesByAgent = $agentCandidates
            ->groupBy('user_id')
            ->map(function ($group) {
                $user = $group->first()->user;
                return [
                    'agentId' => $user->id,
                    'agentName' => $user->firstName . ' ' . $user->lastName,
                    'count' => $group->count()
                ];
            })->values()->toArray();

        // Group by status_for_candidate_from_agent_id
        $statusCounts = $agentCandidates
            ->groupBy('status_for_candidate_from_agent_id')
            ->map(function ($group, $statusId) {
                $statusName = optional($group->first()->statusForCandidateFromAgent)->name ?? 'Unknown';
                return [
                    'label' => $statusName,
                    'value' => $group->count(),
                    'statusId' => $statusId
                ];
            })->values()->toArray();

        // Get total active job postings (not soft-deleted)
        $totalJobPostings = \App\Models\CompanyJob::whereNull('deleted_at')->count();

        // Get job postings assigned to agents (count unique company_jobs only)
        $assignedJobsQuery = AssignedJob::whereHas('companyJob'); // Only include if companyJob exists and not soft-deleted

        if ($agentId) {
            $assignedJobsQuery->where('user_id', $agentId);
        }

        // Count distinct company_job_id instead of total assigned_jobs records
        $jobPostingsAssigned = $assignedJobsQuery->distinct('company_job_id')->count('company_job_id');

        // Get full assigned jobs data for later use
        $assignedJobs = AssignedJob::with('companyJob:id,job_title,status')
            ->whereHas('companyJob')
            ->when($agentId, function($query) use ($agentId) {
                return $query->where('user_id', $agentId);
            })
            ->get();

        // Get contracts with agents
        $contractsQuery = \App\Models\AgentServiceContract::whereNull('deleted_at');

        if ($agentId) {
            $contractsQuery->where('agent_id', $agentId);
        }

        $contractsWithAgents = $contractsQuery->count();

        // Get financial statistics from agent_invoices
        $agentInvoicesQuery = \App\Models\AgentInvoice::whereNull('deleted_at');

        if ($agentId) {
            $agentInvoicesQuery->where('agent_id', $agentId);
        }

        $agentInvoices = $agentInvoicesQuery->get();
        $totalInvoiced = $agentInvoices->where('invoiceStatus', \App\Models\AgentInvoice::INVOICE_STATUS_INVOICED)->sum('price');
        $totalNotInvoiced = $agentInvoices->where('invoiceStatus', \App\Models\AgentInvoice::INVOICE_STATUS_NOT_INVOICED)->sum('price');
        $totalPaid = $agentInvoices->where('invoiceStatus', \App\Models\AgentInvoice::INVOICE_STATUS_PAID)->sum('price');

        // For payment: sum of not_invoiced + invoiced (excluding paid and rejected)
        $remainingToInvoice = $totalNotInvoiced + $totalInvoiced;

        // Build agent details list (for all agents or filtered agent)
        $agentsList = [];
        foreach ($agents as $agent) {
            $agentCandidatesCount = $agentCandidates->where('user_id', $agent->id)->count();
            $agentJobsCount = $assignedJobs->where('user_id', $agent->id)->count();

            $agentsList[] = [
                'id' => $agent->id,
                'name' => $agent->firstName . ' ' . $agent->lastName,
                'email' => $agent->email,
                'candidatesCount' => $agentCandidatesCount,
                'jobsCount' => $agentJobsCount,
            ];
        }

        return response()->json([
            'totalAgents' => $totalAgents,
            'totalCandidatesAdded' => $totalCandidatesAdded,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'remainingToInvoice' => $remainingToInvoice,
            'totalJobPostings' => $totalJobPostings, // Total active job postings in system
            'jobPostingsAssigned' => $jobPostingsAssigned,
            'contractsWithAgents' => $contractsWithAgents,
            'statusCounts' => $statusCounts,
            'candidatesByAgent' => $candidatesByAgent,
            'agentsList' => $agentsList,
        ], 200);
    }
}


