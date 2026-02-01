<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesExport;
use App\Exports\MigrationDocumentPreparationExport;
use App\Models\MigrationDocumentPreparation;
use App\Models\Permission;
use App\Traits\HasRolePermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MigrationDocumentPreparationController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $searchByUser = $request->user_id;
            $searchByCompany = $request->company_id;
            $searchByDateOfPreparationOnDocument = $request->dateOfPreparationOnDocument;
            $searchBySubmissionDate = $request->submissionDate;

            $migrationDocumentPreparation = MigrationDocumentPreparation::select(
                'id',
                'candidate_id',
                'user_id',
                'medicalCertificate',
                'dateOfPreparationOnDocument',
                'submissionDate',
                'authorization',
                'residenceDeclaration',
                'justificationAuthorization',
                'declarationOfForeigners',
                'notarialDeed',
                'conditionsMetDeclaration',
                'jobDescription',
                'employmentContract'
            )
                ->when($searchByUser, function ($query, $searchByUser) {
                    $query->where('user_id', $searchByUser);
                })
                ->when($searchByCompany, function ($query, $searchByCompany) {
                    $query->whereHas('candidate.company', function ($subQuery) use ($searchByCompany) {
                        $subQuery->where('id', $searchByCompany);
                    });
                })
                ->when($searchByDateOfPreparationOnDocument, function ($query, $searchByDateOfPreparationOnDocument) {
                    $query->whereDate('dateOfPreparationOnDocument', $searchByDateOfPreparationOnDocument);
                })
                ->when($searchBySubmissionDate, function ($query, $searchBySubmissionDate) {
                    $query->whereDate('submissionDate', $searchBySubmissionDate);
                })
                ->with([
                    'candidate' => function ($query) {
                        $query->select('id', 'fullName', 'dossierNumber', 'company_id')->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->select('id', 'firstName', 'lastName', 'email')->withTrashed();
                    },
                    'candidate.company' => function ($query) {
                        $query->select('id', 'nameOfCompany', 'phoneOfContactPerson')->withTrashed();
                    }
                ])
                ->paginate();

            return response()->json($migrationDocumentPreparation, 200);
        } catch (\Exception $e) {
            Log::error('Document Preparation could not be retrieved', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Document Preparation could not be retrieved: ' . $e->getMessage()], 409);
        }
    }


    public function export(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $searchByUser = $request->user_id;
            $searchByCompany = $request->company_id;
            $searchByDateOfPreparationOnDocument = $request->dateOfPreparationOnDocument;
            $searchBySubmissionDate = $request->submissionDate;

            $migrationDocumentPreparation = MigrationDocumentPreparation::select(
                'id',
                'candidate_id',
                'user_id',
                'medicalCertificate',
                'dateOfPreparationOnDocument',
                'submissionDate',
                'authorization',
                'residenceDeclaration',
                'justificationAuthorization',
                'declarationOfForeigners',
                'notarialDeed',
                'conditionsMetDeclaration',
                'jobDescription',
                'employmentContract'
            )
                ->when($searchByUser, function ($query, $searchByUser) {
                    $query->where('user_id', $searchByUser);
                })
                ->when($searchByCompany, function ($query, $searchByCompany) {
                    $query->whereHas('candidate.company', function ($subQuery) use ($searchByCompany) {
                        $subQuery->where('id', $searchByCompany);
                    });
                })
                ->when($searchByDateOfPreparationOnDocument, function ($query, $searchByDateOfPreparationOnDocument) {
                    $query->whereDate('dateOfPreparationOnDocument', $searchByDateOfPreparationOnDocument);
                })
                ->when($searchBySubmissionDate, function ($query, $searchBySubmissionDate) {
                    $query->whereDate('submissionDate', $searchBySubmissionDate);
                })
                ->with([
                    'candidate' => function ($query) {
                        $query->select('id', 'fullName', 'dossierNumber', 'company_id')->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->select('id', 'firstName', 'lastName', 'email')->withTrashed();
                    },
                    'candidate.company' => function ($query) {
                        $query->select('id', 'nameOfCompany', 'phoneOfContactPerson')->withTrashed();
                    }
                ])
                ->get();

            $fileName = 'document_preparation_' . Carbon::now()->format('Y-m-d') . '.xlsx';

            Excel::store(new MigrationDocumentPreparationExport($migrationDocumentPreparation), $fileName, 'local');

            $filePath = Storage::disk('local')->path($fileName);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Document Preparation export failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Document Preparation could not be retrieved: ' . $e->getMessage()], 409);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $migrationDocumentPreparation = new MigrationDocumentPreparation();
            $migrationDocumentPreparation->candidate_id = $request->candidate_id;
            $migrationDocumentPreparation->user_id = Auth::user()->id;
            $migrationDocumentPreparation->medicalCertificate = $request->medicalCertificate;
            $migrationDocumentPreparation->dateOfPreparationOnDocument = $request->dateOfPreparationOnDocument;
            $migrationDocumentPreparation->submissionDate = $request->submissionDate;
            $migrationDocumentPreparation->authorization = $request->authorization;
            $migrationDocumentPreparation->residenceDeclaration = $request->residenceDeclaration;
            $migrationDocumentPreparation->justificationAuthorization = $request->justificationAuthorization;
            $migrationDocumentPreparation->declarationOfForeigners = $request->declarationOfForeigners;
            $migrationDocumentPreparation->notarialDeed = $request->notarialDeed;
            $migrationDocumentPreparation->conditionsMetDeclaration = $request->conditionsMetDeclaration;
            $migrationDocumentPreparation->jobDescription = $request->jobDescription;
            $migrationDocumentPreparation->employmentContract = $request->employmentContract;

            if($migrationDocumentPreparation->save()) {
                return response()->json(['message' => 'Document Preparation created successfully'], 201);
            } else {
                Log::info('Document Preparation could not be created');
                return response()->json(['message' => 'Document Preparation'], 409);
            }
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be created in catch', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Document Preparation could not be created'], 409);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MigrationDocumentPreparation  $migrationDocumentPreparation
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $migrationDocumentPreparation = MigrationDocumentPreparation::select(
                'id',
                'candidate_id',
                'user_id',
                'medicalCertificate',
                'dateOfPreparationOnDocument',
                'submissionDate',
                'authorization',
                'residenceDeclaration',
                'justificationAuthorization',
                'declarationOfForeigners',
                'notarialDeed',
                'conditionsMetDeclaration',
                'jobDescription',
                'employmentContract'
            )
                ->with([
                    'candidate' => function ($query) {
                        $query->select('id', 'fullName', 'dossierNumber', 'company_id')->withTrashed();
                    },
                    'user' => function ($query) {
                        $query->select('id', 'firstName', 'lastName', 'email')->withTrashed();
                    },
                    'candidate.company' => function ($query) {
                        $query->select('id', 'nameOfCompany', 'phoneOfContactPerson')->withTrashed();
                    }
                ])
                ->find($id);

            return response()->json($migrationDocumentPreparation, 200);
        } catch (\Exception $e) {
            Log::error('Document Preparation could not be retrieved', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Document Preparation could not be retrieved: ' . $e->getMessage()], 409);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MigrationDocumentPreparation  $migrationDocumentPreparation
     * @return \Illuminate\Http\Response
     */
    public function edit(MigrationDocumentPreparation $migrationDocumentPreparation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MigrationDocumentPreparation  $migrationDocumentPreparation
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $migrationDocumentPreparation = MigrationDocumentPreparation::find($id);
            $migrationDocumentPreparation->candidate_id = $request->candidate_id;
            $migrationDocumentPreparation->user_id = Auth::user()->id;
            $migrationDocumentPreparation->medicalCertificate = $request->medicalCertificate;
            $migrationDocumentPreparation->dateOfPreparationOnDocument = $request->dateOfPreparationOnDocument;
            $migrationDocumentPreparation->submissionDate = $request->submissionDate;
            $migrationDocumentPreparation->authorization = $request->authorization;
            $migrationDocumentPreparation->residenceDeclaration = $request->residenceDeclaration;
            $migrationDocumentPreparation->justificationAuthorization = $request->justificationAuthorization;
            $migrationDocumentPreparation->declarationOfForeigners = $request->declarationOfForeigners;
            $migrationDocumentPreparation->notarialDeed = $request->notarialDeed;
            $migrationDocumentPreparation->conditionsMetDeclaration = $request->conditionsMetDeclaration;
            $migrationDocumentPreparation->jobDescription = $request->jobDescription;
            $migrationDocumentPreparation->employmentContract = $request->employmentContract;

            if($migrationDocumentPreparation->save()) {
                return response()->json(['message' => 'Document Preparation updated successfully'], 201);
            } else {
                return response()->json(['message' => 'Document Preparation could not be updated'], 409);
            }
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be updated', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Document Preparation could not be updated'], 409);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MigrationDocumentPreparation  $migrationDocumentPreparation
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!$this->checkPermission(Permission::DOCUMENTS_PREPARATION)) {
            return response()->json([
                "status" => "error",
                "message" => "Unauthorized access",
            ], 403);
        }
        
        try {
            $migrationDocumentPreparation = MigrationDocumentPreparation::find($id);
            if($migrationDocumentPreparation->delete()) {
                return response()->json(['message' => 'Document Preparation deleted successfully'], 200);
            } else {
                return response()->json(['message' => 'Document Preparation could not be deleted'], 409);
            }
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be deleted', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Document Preparation could not be deleted'], 409);
        }
    }
}
