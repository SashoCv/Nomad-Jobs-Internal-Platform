<?php

namespace App\Http\Controllers;

use App\Http\Resources\CandidatesHistoryResource;
use App\Jobs\SendEmailForArrivalStatusCandidates;
use App\Jobs\SendEmailToCompany;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\CompanyCategory;
use App\Models\File;
use App\Models\Status;
use App\Models\Statushistory;
use App\Repository\NotificationRepository;
use App\Repository\UsersNotificationRepository;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\Shared\ZipArchive;

class ArrivalCandidateController extends Controller
{
    const ARRIVAL_EXPECTED_STATUS_ID = 18; // "Има билет" status

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $statusId = $request->status_id;
            $fromDate = $request->from_date;
            $toDate = $request->to_date;

            // Pre-load all statuses once
            $allStatuses = \App\Models\Status::all()->keyBy('id');
            $statusesByOrder = $allStatuses->keyBy('order');

            // Use relationships instead of joins to avoid duplicates
            $query = Candidate::with(['company', 'status'])
                ->whereHas('status', function ($q) {
                    $q->where('showOnHomePage', 1);
                });

            // Simple status filter!
            if ($statusId) {
                $query->where('status_id', $statusId);
            }

            // Date filters on status history
            if ($fromDate || $toDate) {
                $query->whereHas('statusHistories', function ($q) use ($fromDate, $toDate, $statusId) {
                    if ($statusId) {
                        $q->where('status_id', $statusId);
                    }
                    if ($fromDate) {
                        $fromDate = Carbon::createFromFormat('m-d-Y', $fromDate)->format('Y-m-d');
                        $q->where('statusDate', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $toDate = Carbon::createFromFormat('m-d-Y', $toDate)->format('Y-m-d');
                        $q->where('statusDate', '<=', $toDate);
                    }
                });
            }

            // Load arrivals relationship for all candidates
            $query->with('arrivals');

            // Sorting - for arrival expected status, we'll sort using a subquery
            if ($statusId == self::ARRIVAL_EXPECTED_STATUS_ID) {
                $query->leftJoin('arrivals', 'candidates.id', '=', 'arrivals.candidate_id')
                    ->select('candidates.*')
                    ->distinct()
                    ->orderByRaw('arrivals.arrival_date IS NULL')
                    ->orderBy('arrivals.arrival_date', 'desc');
            } else {
                $query->orderBy('updated_at', 'desc');
            }

            // Get paginated results first
            $arrivalCandidates = $query->paginate();

            // Get candidate IDs for file existence check
            $candidateIds = $arrivalCandidates->pluck('id')->toArray();
            $candidatesWithFiles = File::whereIn('candidate_id', $candidateIds)
                ->distinct('candidate_id')
                ->pluck('candidate_id')
                ->keyBy(function ($id) { return $id; });

            $arrivalCandidates->getCollection()->transform(function ($candidate) use ($allStatuses, $statusesByOrder, $candidatesWithFiles) {
                $currentStatusId = $candidate->status_id;
                $currentStatus = $candidate->status;
                $currentStatusOrder = $currentStatus ? $currentStatus->order : null;

                // Calculate availableStatuses efficiently
                $availableStatuses = [];
                $addArrival = false;

                $arrival = $candidate->arrivals->first();

                if ($currentStatusId) {
                    if ($currentStatusId === self::ARRIVAL_EXPECTED_STATUS_ID && (!$arrival || !$arrival->arrival_date)) {
                        $addArrival = true;
                    }

                    $nextStatusOrder = $currentStatusOrder + 1;
                    $nextStatus = $statusesByOrder->get($nextStatusOrder);

                    if ($nextStatus) {
                        $nextStatusId = $nextStatus->id;
                        $availableStatuses = [$nextStatusId, 11, 12, 13, 14];

                        if ($nextStatusId === self::ARRIVAL_EXPECTED_STATUS_ID) {
                            $addArrival = true;
                        }
                    } else {
                        if ($currentStatusId >= self::ARRIVAL_EXPECTED_STATUS_ID) {
                            $availableStatuses = [11, 12, 13, 14];
                            $higherStatuses = $allStatuses->where('order', '>', $currentStatusOrder)->pluck('id')->toArray();
                            $availableStatuses = array_unique(array_merge($availableStatuses, $higherStatuses));
                        } else {
                            $availableStatuses = $allStatuses->pluck('id')->toArray();
                        }
                    }
                } else {
                    $availableStatuses = $allStatuses->pluck('id')->toArray();
                }

                // Build arrival info if status is 18
                $arrivalInfo = null;
                if ($currentStatusId == self::ARRIVAL_EXPECTED_STATUS_ID && $arrival && $arrival->arrival_date) {
                    $arrivalInfo = [
                        'id' => $arrival->id,
                        'arrival_date' => $arrival->arrival_date,
                        'arrival_time' => $arrival->arrival_time,
                        'arrival_flight' => $arrival->arrival_flight,
                        'arrival_location' => $arrival->arrival_location,
                        'where_to_stay' => $arrival->where_to_stay,
                        'phone_number' => $arrival->phone_number,
                    ];
                }

                return [
                    'id' => $candidate->id,
                    'fullName' => $candidate->fullName,
                    'fullNameCyrillic' => $candidate->fullNameCyrillic,
                    'contractType' => $candidate->contractType,
                    'company_id' => $candidate->company_id,
                    'companyName' => $candidate->company?->nameOfCompany,
                    'phoneNumber' => $candidate->phoneNumber,
                    'availableStatuses' => $availableStatuses,
                    'addArrival' => $addArrival,
                    'has_files' => $candidatesWithFiles->has($candidate->id),
                    'statusHistories' => [
                        'status_id' => $candidate->status_id,
                        'statusName' => $currentStatus ? $currentStatus->nameOfStatus : null,
                        'statusDate' => $candidate->updated_at ? $candidate->updated_at->format('Y-m-d') : null,
                        'arrivalInfo' => $arrivalInfo,
                    ],
                ];
            });

            return response()->json([
                'arrivalCandidates' => $arrivalCandidates,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving arrival candidates.',
                'error' => $e->getMessage(),
            ], 500);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $arrivalCandidate = new ArrivalCandidate();
            $arrivalCandidate->arrival_id = $request->arrival_id;
            $arrivalCandidate->status_arrival_id = $request->status_arrival_id;
            $arrivalCandidate->status_description = $request->status_description;
            $arrivalCandidate->status_date = $request->status_date;

            $arrivalCandidate->save();

            return response()->json([
                'message' => 'Arrival Candidate created successfully',
                'arrivalCandidate' => $arrivalCandidate
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransportForCandidates(Request $request)
    {
        try {
            $filters = [
                'searchCompany' => $request->searchCompany,
                'searchCandidate' => $request->searchCandidate,
                'searchContract' => $request->searchContract,
                'searchCoveredBy' => $request->searchCoveredBy,
                'searchBilled' => $request->searchBilled,
            ];

            $transport = DB::table('arrivals')
                ->join('candidates', 'arrivals.candidate_id', '=', 'candidates.id')
                ->join('statushistories', 'candidates.id', '=', 'statushistories.candidate_id')
                ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
                ->join('companies', 'candidates.company_id', '=', 'companies.id')
                ->leftJoin('company_service_contracts', 'candidates.company_id', '=', 'company_service_contracts.company_id')
                ->leftJoin('arrival_pricings', 'arrivals.id', '=', 'arrival_pricings.arrival_id')
                ->where('statuses.nameOfStatus', 'Пристигнал')
                ->orderBy('arrivals.arrival_date', 'desc')
                ->select(
                    'arrivals.id as arrival_id',
                    'candidates.id as candidate_id',
                    'candidates.company_id as company_id',
                    DB::raw('company_service_contracts.id as contract_id'),
                    'statuses.id as status_id',
                    'candidates.fullName',
                    'candidates.fullNameCyrillic',
                    'companies.nameOfCompany as companyName',
                    DB::raw("DATE_FORMAT(arrivals.arrival_date, '%d.%m.%Y') as statusDate"),
                    'statuses.nameOfStatus as statusName',
                    DB::raw('company_service_contracts.contractNumber as contractNumber'),
                    'arrivals.arrival_flight as arrivalType',
                    'arrival_pricings.price as price',
                    'arrival_pricings.margin as margin',
                    'arrival_pricings.total as total',
                    'arrival_pricings.billed as billed',
                    'arrival_pricings.isTransportCoveredByNomad as isTransportCoveredByNomad'
                );

            // Apply filters
            if ($filters['searchCompany']) {
                $transport->where('companies.nameOfCompany', 'like', '%' . $filters['searchCompany'] . '%');
            }

            if ($filters['searchCandidate']) {
                $transport->where(function ($query) use ($filters) {
                    $query->where('candidates.fullName', 'like', '%' . $filters['searchCandidate'] . '%')
                        ->orWhere('candidates.fullNameCyrillic', 'like', '%' . $filters['searchCandidate'] . '%');
                });
            }

            if ($filters['searchContract']) {
                $transport->where('company_service_contracts.contractNumber', 'like', '%' . $filters['searchContract'] . '%');
            }

            if (isset($filters['searchCoveredBy'])) {
                $transport->whereNotNull('arrival_pricings.id')
                    ->where('arrival_pricings.isTransportCoveredByNomad', $filters['searchCoveredBy']);
            }

            if ($filters['searchBilled']) {
                $transport->where('arrival_pricings.billed', $filters['searchBilled']);
            }

            $transport = $transport->paginate();

            return response()->json($transport);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\Response
     */
    public function edit(ArrivalCandidate $arrivalCandidate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Check if the requested status already exists for this candidate
            $status_id = $request->status_id;
            $sendEmail = $request->sendEmail ?? false;
            $description = $request->description ?? null;
            $statusDate = $request->statusDate ?? Carbon::now()->format('m-d-Y');
            $candidate_id = $id;


            $existingRequestedStatus = Statushistory::where('candidate_id', $id)
                ->where('status_id', $status_id)
                ->first();

            if ($existingRequestedStatus) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'This status already exists for the candidate.',
                ], 422);
            }

            if (!in_array($status_id, [11, 12, 13, 14, 19])) {
                $allStatuses = Status::where('order', '<=', Status::find($status_id)->order)
                    ->pluck('id')
                    ->toArray();
            } else {
                $allStatuses = [$status_id];
            }

            foreach ($allStatuses as $status) {
                $existingStatus = Statushistory::where('candidate_id', $candidate_id)
                    ->where('status_id', $status)
                    ->first();

                if (!$existingStatus) {
                    $newStatus = new Statushistory();
                    $newStatus->candidate_id = $candidate_id;
                    $newStatus->status_id = $status;
                    $newStatus->statusDate = Carbon::createFromFormat('m-d-Y', $statusDate)->format('Y-m-d');
                    $newStatus->description = $description;
                    $newStatus->save();

                    InvoiceService::saveInvoiceOnStatusChange($candidate_id, $status, $statusDate);

                }
            }

            // Update the candidate's current status (CRITICAL FIX!)
            $candidate = Candidate::find($candidate_id);
            if ($candidate) {
                $candidate->status_id = $status_id;
                $candidate->save();
            }

            dispatch(new SendEmailForArrivalStatusCandidates($request->status_id, $candidate_id, $request->statusDate, $sendEmail));


            return response()->json([
                'message' => 'Arrival Candidate updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::info('Error updating arrival candidate: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ArrivalCandidate  $arrivalCandidate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $arrivalCandidate = ArrivalCandidate::find($id);
            $arrivalCandidate->delete();

            return response()->json('Arrival Candidate deleted successfully');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function downloadDocumentsForArrivalCandidates($candidateId)
    {

        $candidateCategoryId = Category::where('candidate_id', $candidateId)->where('nameOfCategory', 'Documents For Arrival Candidates')->first()->id;


        $files = File::where('candidate_id', $candidateId)->where('category_id', $candidateCategoryId)->get(['fileName', 'filePath']);

        if(!$files){
            return response()->json(['message' => 'Files not found'], 404);
        }
        $candidate = Candidate::find($candidateId);

        $zip = new ZipArchive();
        $zipFileName = $candidate->fullName . '_arrival_documents.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                $filePath = public_path('storage/' . $file->filePath);
                if (file_exists($filePath)) {
                    $fileName = $file->fileName;
                    $fileExtension = substr(strrchr($filePath, '.'), 1);
                    $fileName .= '.' . $fileExtension;
                    $zip->addFile($filePath, $fileName);
                }
            }
            $zip->close();

            return response()->download($zipFilePath, $zipFileName);
        } else {
            return response()->json(['message' => 'Failed to create the zip file'], 500);
        }
    }


    public function getArrivalCandidates(Request $request)
    {
        try {
            $dateFrom = $request->dateFrom;
            $dateTo = $request->dateTo;
            $statusId = $request->statusId ?? 1;

            $candidatesWithStatuses = Statushistory::with(['candidate', 'status', 'candidate.arrival','candidate.company'])
                ->whereHas('status', function ($query) use ($statusId) {
                    $query->where('id', $statusId);
                });

            if ($dateFrom) {
                $candidatesWithStatuses->whereHas('status', function ($query) use ($dateFrom) {
                    $query->where('statusDate', '>=', $dateFrom);
                });
            }

            if ($dateTo) {
                $candidatesWithStatuses->whereHas('status', function ($query) use ($dateTo) {
                    $query->where('statusDate', '<=', $dateTo);
                });
            }

            $candidatesWithStatuses->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
                ->orderBy('statushistories.statusDate', 'desc');

            $arrivalCandidates = $candidatesWithStatuses->paginate();

            return CandidatesHistoryResource::collection($arrivalCandidates);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving arrival candidates.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
