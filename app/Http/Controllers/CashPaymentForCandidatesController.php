<?php

namespace App\Http\Controllers;

use App\Models\CashPaymentForCandidates;
use Illuminate\Http\Request;

class CashPaymentForCandidatesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filterCandidateId = $request->candidate_id;
        $filterUserId = $request->user_id;
        $paymentDate = $request->paymentDate;

        $cashPaymentForCandidates = CashPaymentForCandidates::with([
            'candidate:id,fullName,email',
            'user:id,firstName,lastName',
            'candidate.company:id,nameOfCompany',
        ]);

        if ($filterCandidateId) {
            $cashPaymentForCandidates = $cashPaymentForCandidates->where('candidate_id', $filterCandidateId);
        }

        if ($filterUserId) {
            $cashPaymentForCandidates = $cashPaymentForCandidates->where('user_id', $filterUserId);
        }

        if ($paymentDate) {
            $cashPaymentForCandidates = $cashPaymentForCandidates->where('paymentDate', $paymentDate);
        }

        $cashPaymentForCandidates = $cashPaymentForCandidates->paginate();

        return response()->json([
            'status' => 200,
            'data' => $cashPaymentForCandidates
        ]);
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
        $cashPaymentForCandidates = new CashPaymentForCandidates();
        $cashPaymentForCandidates->fill($request->only([
            'candidate_id',
            'user_id',
            'amount',
            'purposeOfPayment',
            'paymentDate',
            'isPaid'
        ]));


       if($cashPaymentForCandidates->save()) {
            return response()->json([
                'status' => 200,
                'message' => 'Cash Payment for Candidates created successfully'
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating Cash Payment for Candidates'
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashPaymentForCandidates  $cashPaymentForCandidates
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $cashPaymentForCandidates = CashPaymentForCandidates::with([
            'candidate:id,fullName,email',
            'user:id,firstName,lastName',
            'candidate.company:id,nameOfCompany',
        ])->find($id);

        return response()->json([
            'status' => 200,
            'data' => $cashPaymentForCandidates
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CashPaymentForCandidates  $cashPaymentForCandidates
     * @return \Illuminate\Http\Response
     */
    public function edit(CashPaymentForCandidates $cashPaymentForCandidates)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashPaymentForCandidates  $cashPaymentForCandidates
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $cashPaymentForCandidates = CashPaymentForCandidates::find($id);
        $cashPaymentForCandidates->fill($request->only([
            'candidate_id',
            'user_id',
            'amount',
            'purposeOfPayment',
            'paymentDate',
            'isPaid'
        ]));

        if($cashPaymentForCandidates->save()) {
            return response()->json([
                'status' => 200,
                'message' => 'Cash Payment for Candidates updated successfully'
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating Cash Payment for Candidates'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashPaymentForCandidates  $cashPaymentForCandidates
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $cashPaymentForCandidates = CashPaymentForCandidates::find($id);

        if($cashPaymentForCandidates->delete()) {
            return response()->json([
                'status' => 200,
                'message' => 'Cash Payment for Candidates deleted successfully'
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting Cash Payment for Candidates'
            ]);
        }
    }
}
