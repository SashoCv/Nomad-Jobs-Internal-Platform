<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailForArrivalStatusCandidates;
use App\Jobs\SendEmailToCompany;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\CompanyCategory;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $query = ArrivalCandidate::with(['arrival.candidate', 'statusArrival']);

            if ($statusId) {
                $query->where('status_arrival_id', $statusId);
            }

            if($fromDate && $toDate) {
                $fromDate = Carbon::createFromFormat('m-d-Y', $fromDate)->format('d-m-Y');
                $toDate = Carbon::createFromFormat('m-d-Y', $toDate)->format('d-m-Y');
                $query->whereBetween('status_date', [$fromDate, $toDate]);
            }

            $query->orderByRaw("STR_TO_DATE(status_date, '%d.%m.%Y') ASC");

            $arrivalCandidates = $query->paginate();

            $arrivalCandidates->getCollection()->transform(function ($arrivalCandidate) {
                $candidateId = $arrivalCandidate->arrival->candidate->id ?? null;

                if ($candidateId) {
                    $candidateCategoryId = Category::where('candidate_id', $candidateId)
                        ->where('nameOfCategory', 'Documents For Arrival Candidates')
                        ->first()
                        ->id ?? null;

                    if ($candidateCategoryId) {
                        $files = File::where('candidate_id', $candidateId)
                            ->where('category_id', $candidateCategoryId)
                            ->exists();

                        $arrivalCandidate->has_files = $files ? true : false;
                    } else {
                        $arrivalCandidate->has_files = false;
                    }
                }

                return $arrivalCandidate;
            });

            return response()->json([
                'message' => 'Arrival Candidates retrieved successfully',
                'arrivalCandidates' => $arrivalCandidates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving arrival candidates.',
                'error' => $e->getMessage()
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
    public function update(Request $request)
    {
        try {
            $arrivalCandidate = ArrivalCandidate::find($request->id);
            $arrivalCandidate->status_arrival_id = $request->status_arrival_id;
            $arrivalCandidate->status_description = $request->status_description;
            $arrivalCandidate->status_date = $request->status_date;

            $candidateId = Arrival::where('id', $arrivalCandidate->arrival_id)->first()->candidate_id;
            $candidate = Candidate::where('id', $candidateId)->first();

            if ($arrivalCandidate->save()) {

                $statusMapping = [
                    1 => 5,  // Pristignal
                    3 => 6,  // Procedura za ERPR
                    4 => 17, // Procedura za pismo
                    5 => 7,  // Snimka za ERPR
                    6 => 8,  // Poluchava ERPR
                    7 => 4,  // Polucil Viza
                    8 => 18, // Ocakva se Kandidat
                    9 => 9,  // Naznachen za rabota
                ];

                if (isset($statusMapping[$arrivalCandidate->status_arrival_id])) {
                    $newStatusId = $statusMapping[$arrivalCandidate->status_arrival_id];

                    if ($candidate->status_id !== $newStatusId) {
                        $candidate->status_id = $newStatusId;
                        $candidate->save();
                    }
                }

                if($arrivalCandidate->status_arrival_id == 1) {
                   dispatch(new SendEmailToCompany($arrivalCandidate->id));
                }

                dispatch(new SendEmailForArrivalStatusCandidates($arrivalCandidate->id));
            }

            return response()->json([
                'message' => 'Arrival Candidate updated successfully',
                'arrivalCandidate' => $arrivalCandidate,
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
}
