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
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\Shared\ZipArchive;

class ArrivalCandidateController extends Controller
{
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

            $query = Candidate::with(['latestStatusHistory.status', 'company'])
                ->whereHas('latestStatusHistory.status', function ($q) {
                    $q->where('showOnHomePage', 1);
                });

            if ($statusId) {
                $query->whereHas('latestStatusHistory', function ($q) use ($statusId) {
                    $q->where('status_id', $statusId);
                });
            }

            if ($fromDate) {
                $fromDate = Carbon::createFromFormat('m-d-Y', $fromDate)->format('Y-m-d');
            }

            if ($toDate) {
                $toDate = Carbon::createFromFormat('m-d-Y', $toDate)->format('Y-m-d');
            }

            if ($fromDate && $toDate) {
                $query->whereHas('latestStatusHistory', function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween('statusDate', [$fromDate, $toDate]);
                });
            } elseif ($fromDate) {
                $query->whereHas('latestStatusHistory', function ($q) use ($fromDate) {
                    $q->where('statusDate', '>=', $fromDate);
                });
            } elseif ($toDate) {
                $query->whereHas('latestStatusHistory', function ($q) use ($toDate) {
                    $q->where('statusDate', '<=', $toDate);
                });
            }



            // Note: Cannot use orderBy inside whereHas - has no effect here
            // You may sort statusHistories manually later if needed

            $arrivalCandidates = $query->paginate();

            $arrivalCandidates->getCollection()->transform(function ($candidate) use ($statusId) {
                $latestStatus = $candidate->latestStatusHistory;

                $arrivalInfo = null;
                if ($latestStatus && $latestStatus->status_id == 18) {
                    $arrivalInfo = \DB::table('arrivals')
                        ->where('statushistories_id', $latestStatus->id)
                        ->first();
                }

                return [
                    'id' => $candidate->id,
                    'fullName' => $candidate->fullName,
                    'fullNameCyrillic' => $candidate->fullNameCyrillic,
                    'contractType' => $candidate->contractType,
                    'company_id' => $candidate->company_id,
                    'companyName' => $candidate->company?->nameOfCompany,
                    'phoneNumber' => $candidate->phoneNumber,
                    'statusHistories' => $latestStatus ? [
                        'id' => $latestStatus->id,
                        'description' => $latestStatus->description,
                        'status_id' => $latestStatus->status_id,
                        'statusName' => $latestStatus->status?->nameOfStatus,
                        'statusDate' => \Carbon\Carbon::parse($latestStatus->statusDate)->format('d-m-Y'),
                        'arrivalInfo' => $arrivalInfo,
                    ] : null,
                ];
            });

            return response()->json([
                'arrivalCandidates' => $arrivalCandidates, // paginator with meta
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
     * @return \Illuminate\Http\Response
     */
    public function show(ArrivalCandidate $arrivalCandidate)
    {
        //
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

            $statusHistory = new Statushistory();
            $statusHistory->candidate_id = $id;
            $statusHistory->status_id = $request->status_id;
            $statusHistory->statusDate = Carbon::createFromFormat('m-d-Y', $request->statusDate)->format('Y-m-d');
            $statusHistory->description = $request->description;

            if ($statusHistory->save()) {
                dispatch(new SendEmailForArrivalStatusCandidates($request->status_id, $id, $request->statusDate));
            }

            return response()->json([
                'message' => 'Arrival Candidate updated successfully',
                'arrivalCandidate' => $statusHistory,
            ]);
        } catch (\Exception $e) {
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
