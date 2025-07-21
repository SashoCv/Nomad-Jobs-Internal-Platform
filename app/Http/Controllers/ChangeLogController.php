<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use Illuminate\Http\Request;

class ChangeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $changeLogs = ChangeLog::with(['user','user.company'])->get();

            return response()->json([
                "status" => "success",
                "message" => "Change logs retrieved successfully",
                "data" => $changeLogs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to retrieve change logs",
                "error" => $e->getMessage()
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
            $data = $request->validate([
                'tableName' => 'required|string|max:255',
                'record_id' => 'required|integer',
                'fieldName' => 'required|string|max:255',
                'oldValue' => 'nullable|string',
                'newValue' => 'nullable|string',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $data['status'] = 'pending'; // Default status
            $data['isApplied'] = false; // Default isApplied value
            $data['company_id'] = $request->company_id ?? auth()->user()->company_id; // Assuming the user is authenticated and has a company_id

            $changeLog = ChangeLog::create($data);

            return response()->json([
                "status" => "success",
                "message" => "Change log stored successfully",
                "data" => $changeLog
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to store change log",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function approveLog($changeLogId)
    {
        try {
            $changeLog = ChangeLog::findOrFail($changeLogId);
            $changeLog->status = 'approved';
            $changeLog->isApplied = true;
            $changeLog->save();

            $tableName = $changeLog->tableName;
            $recordId = $changeLog->record_id;
            $fieldName = $changeLog->fieldName;
            $newValue = $changeLog->newValue;

            if ($tableName === 'company_jobs') {
                $model = \App\Models\CompanyJob::findOrFail($recordId);
            } elseif ($tableName === 'contract_pricing') {
                $model = \App\Models\ContractPricing::findOrFail($recordId);
            } else {
                return response()->json([
                    "status" => "error",
                    "message" => "Invalid table name"
                ], 400);
            }

            $model->{$fieldName} = $newValue;
            $model->save();

            return response()->json([
                "status" => "success",
                "message" => "Change log approved successfully",
                "data" => $changeLog
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to approve change log",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChangeLog  $changeLog
     * @return \Illuminate\Http\Response
     */
    public function show(ChangeLog $changeLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChangeLog  $changeLog
     * @return \Illuminate\Http\Response
     */
    public function edit(ChangeLog $changeLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChangeLog  $changeLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChangeLog $changeLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChangeLog  $changeLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(ChangeLog $changeLog)
    {
        //
    }
}
