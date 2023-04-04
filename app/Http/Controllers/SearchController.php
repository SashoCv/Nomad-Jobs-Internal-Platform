<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class SearchController extends Controller
{

    public function searchName(Request $request)
    {
        if ($request->searchName) {

            $result = Candidate::where('firstName', 'LIKE', '%' . $request->searchName . '%')
                ->orWhere('lastName', 'LIKE', '%' . $request->searchName . '%')
                ->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $result
            ]);
        }
    }


    public function searchCompany(Request $request)
    {
        if ($request->searchCompany) {

            $value = $request->searchCompany;

            $result = Candidate::with(['company' => function ($q) use ($value) {
                $q->where('nameOfCompany', 'LIKE', '%' . $value . '%');
            }])->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $result
            ]);
        }
    }


    public function searchStatus(Request $request)
    {
        if ($request->searchStatus) {

            $value = $request->searchStatus;

            $result = Candidate::with(['status' => function ($q) use ($value) {
                $q->where('nameOfStatus', 'LIKE', '%' . $value . '%');
            }])->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $result
            ]);
        }
    }
}
