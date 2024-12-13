<?php

namespace App\Http\Controllers;

use App\Models\MigrationDocumentPreparation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MigrationDocumentPreparationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
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
                'notarialDeed'
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
                    'candidate:id,fullName,dossierNumber,company_id',
                    'user:id,firstName,lastName,email',
                    'candidate.company:id,nameOfCompany,email'
                ])
                ->get();

            return response()->json($migrationDocumentPreparation, 200);
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be retrieved', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Document Preparation could not be retrieved'], 409);
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

            if($migrationDocumentPreparation->save()) {
                return response()->json(['message' => 'Document Preparation created successfully'], 201);
            } else {
                return response()->json(['message' => 'Document Preparation could not be created'], 409);
            }
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be created', ['message' => $e->getMessage()]);
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
                'notarialDeed'
            )
                ->with([
                    'candidate:id,fullName,dossierNumber,company_id',
                    'user:id,firstName,lastName,email',
                    'candidate.company:id,nameOfCompany,email'
                ])
                ->find($id);

            return response()->json($migrationDocumentPreparation, 200);
        } catch (\Exception $e) {
            Log::info('Document Preparation could not be retrieved', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Document Preparation could not be retrieved'], 409);
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
