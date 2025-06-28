<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentCandidateResource;
use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\CompanyFile;
use App\Models\UserOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{

    public function searchCandidate(Request $request)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('company_id', '=', $request->searchCompany)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)
                    ->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {
                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->orderBy('date', 'DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('status_id', '=', $request->searchStatus)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $request->searchCompany)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            }
        } else {
            if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('company_id', '=', $request->searchCompany)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)
                    ->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {
                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            }
        }
    }

    public function searchWorker(Request $request)
    { {
            if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

                if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)
                        ->where('company_id', '=', $request->searchCompany)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)
                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('company_id', '=', $request->searchCompany)
                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)

                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {
                    $result = Candidate::with('company')->where('type_id', '=', 2)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                }
            } else {
                if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)
                        ->where('company_id', '=', $request->searchCompany)
                        ->where('company_id', '=', Auth::user()->company_id)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)
                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                        ->where('company_id', '=', Auth::user()->company_id)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('company_id', '=', $request->searchCompany)
                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)
                        ->where('company_id', '=', Auth::user()->company_id)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)

                        ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                        ->where('company_id', '=', Auth::user()->company_id)
                        ->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {
                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                    $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                    $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->get();

                    return response()->json([
                        'success' => true,
                        'status' => 200,
                        'data' => $result
                    ]);
                }
            }
        }
    }

    public function searchCompany(Request $request)
    {
        $EIK = $request->input('EIK');
        $nameOfCompany = $request->input('nameOfCompany');
        $status = $request->input('status_id');
        $contractType = $request->input('contractType');
        $companyId = $request->input('company_id');

        $companiesQuery = Company::with(['industry', 'candidates','company_addresses']);

        if (Auth::user()->role_id === 5) {
            $companyOwner = UserOwner::where('user_id', Auth::user()->id)->get();
            $companyIds = $companyOwner->pluck('company_id');

            $companiesQuery->whereIn('id', $companyIds);
        }

        if ($EIK) {
            $companiesQuery->where('EIK', $EIK);
        }

        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }

        if ($nameOfCompany) {
            $companiesQuery->where('nameOfCompany', 'LIKE', "%$nameOfCompany%");
        }

        if ($status) {
            $companiesQuery->whereHas('candidates', function ($query) use ($status) {
                $query->where('status_id', $status);
            });
        }

        if ($contractType) {
            $companiesQuery->whereHas('candidates', function ($query) use ($contractType) {
                $query->where('contractType', $contractType);
            });
        }


        $companies = $companiesQuery->orderBy('id', 'DESC')->paginate(20);

        return response()->json(['companies' => $companies]);
    }
    public function searchDocuments(Request $request)
    {
        $company_id = $request->input('company_id');
        $fullName = $request->input('fullName');

        $candidateDocuments = null;
        $companyDocuments = null;

        if ($company_id) {
            $companyDocuments = CompanyFile::with('company')
                ->where('company_id', '=', $company_id)
                ->get();
        } elseif ($fullName) {
            $candidateDocuments = Candidate::with('files')
                ->where(function ($query) use ($fullName) {
                    $query->where('fullName', 'LIKE', "%$fullName%")
                        ->orWhere('fullNameCyrillic', 'LIKE', "%$fullName%");
                })
                ->get();
        }

        $documents = [];

        if ($candidateDocuments) {
            $documents = $candidateDocuments->toArray();
        } elseif ($companyDocuments) {
            $documents = $companyDocuments->toArray();
        }

        if (empty($documents)) {
            return response()->json(['message' => 'No documents found for the provided candidate or company.'], 404);
        }

        return response()->json(['documents' => $documents]);
    }

    public function searchCandidateNew(Request $request)
    {
        $searchEverything = $request->searchEverything;

        $query = Candidate::with([
            'company',
            'position',
            'user',
            'latestStatusHistory',
            'latestStatusHistory.status',
        ]);

        $userRoleId = Auth::user()->role_id;

        if ($userRoleId === 1 || $userRoleId === 2) {
            $query->where('type_id', '!=', 3);
        }

        if ($userRoleId === 3) {
            $query->where('company_id', Auth::user()->company_id);
        }

        if ($userRoleId === 4) {
            $searchName = $request->searchName;
            $searchCompanyJob = $request->searchCompanyJob;
            $searchAgentStatus = $request->searchAgentStatus;
            $searchNationality = $request->searchNationality;
            $searchCreatedAt = $request->searchCreatedAt;

            $candidatesQuery = AgentCandidate::with(['candidate', 'companyJob', 'companyJob.company', 'statusForCandidateFromAgent', 'user'])
                ->join('company_jobs', 'agent_candidates.company_job_id', '=', 'company_jobs.id')
                ->where('agent_candidates.user_id', Auth::user()->id);

            $candidatesQuery->when($searchName, function ($q) use ($searchName) {
                $q->whereHas('candidate', function ($subquery) use ($searchName) {
                    $subquery->where('fullName', 'LIKE', '%' . $searchName . '%')
                        ->orWhere('fullNameCyrillic', 'LIKE', '%' . $searchName . '%');
                });
            });

            if ($searchCreatedAt) {
                $candidatesQuery->whereRaw("DATE(agent_candidates.created_at) = ?", [$searchCreatedAt]);
            }

            if($searchCompanyJob){
                $candidatesQuery->where('company_jobs.id', '=', $searchCompanyJob);
            }

            if($searchAgentStatus){
                $candidatesQuery->where('status_for_candidate_from_agent_id', '=', $searchAgentStatus);
            }

            if($searchNationality){
                $candidatesQuery->whereHas('candidate', function ($subquery) use ($searchNationality) {
                    $subquery->where('nationality', 'LIKE', '%' . $searchNationality . '%');
                });
            }

            $candidates = $candidatesQuery->paginate(20);
            return AgentCandidateResource::collection($candidates);
        }

        if ($userRoleId === 5) {
            $companyOwner = UserOwner::where('user_id', Auth::user()->id)->get();
            $companyIds = $companyOwner->pluck('company_id');

            $query->whereIn('company_id', $companyIds);
        }


        if (!$searchEverything) {
            $query->when($request->searchName, function ($q) use ($request) {
                $q->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->orWhere('fullNameCyrillic', 'LIKE', '%' . $request->searchName . '%');
            })
                ->when($request->searchQuartal, function ($q) use ($request) {
                    $q->where('quartal', '=', $request->searchQuartal);
                })
                ->when($request->searchSeasonal, function ($q) use ($request) {
                    $q->where('seasonal', '=', $request->searchSeasonal);
                })
                ->when($request->searchCompany, function ($q) use ($request) {
                    $q->where('company_id', '=', $request->searchCompany);
                })
                ->when($request->searchStatus, function ($q) use ($request) {
                    $q->whereHas('latestStatusHistory', function ($query) use ($request) {
                        $query->where('status_id', $request->searchStatus);
                    });
                })
                ->when($request->searchDate, function ($q) use ($request) {
                    $q->whereHas('latestStatusHistory', function ($query) use ($request) {
                        $query->where('statusDate', $request->searchDate);
                    });
                })
                ->when($request->dossierNumber, function ($q) use ($request) {
                    $q->where('dossierNumber', '=', $request->dossierNumber);
                })
                ->when($request->contractType, function ($q) use ($request) {
                    $q->where('contractType', '=', $request->contractType);
                })
                ->when($request->searchAddedBy, function ($q) use ($request) {
                    if ($request->searchAddedBy === 'notDefined') {
                        $q->whereNull('addedBy');
                    } else {
                        $q->where('addedBy', '=', $request->searchAddedBy);
                    }
                })
                ->when($request->searchCaseId, function ($q) use ($request) {
                    $q->where('case_id', '=', $request->searchCaseId);
                })
                ->when($request->nationality, function ($q) use ($request) {
                    $q->where('nationality', 'LIKE', '%' . $request->nationality . '%');
                })
                ->when($request->user_id, function ($q) use ($request) {
                    $q->where('user_id', '=', $request->user_id);
                });

            $result = $query->orderBy('id', 'DESC')->paginate(20);
        }

        if ($request->searchEverything) {
            $query->where(function ($query) use ($request) {
                $searchTerm = '%' . $request->searchEverything . '%';

                $query->where('fullName', 'LIKE', $searchTerm)
                    ->orWhere('fullNameCyrillic', 'LIKE', $searchTerm)
                    ->orWhereHas('company', function ($subquery) use ($searchTerm) {
                        $subquery->where('nameOfCompany', 'LIKE', $searchTerm);
                    })
                    ->orWhereHas('status', function ($subquery) use ($searchTerm) {
                        $subquery->where('nameOfStatus', 'LIKE', $searchTerm);
                    })
                    ->orWhereDate('date', '=', $request->searchEverything)
                    ->orWhere('dossierNumber', '=', $request->searchEverything);
            });

            $result = $query->get();
        }

        if ($userRoleId === 3 || $userRoleId === 5) {
            if ($result instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $result->getCollection()->transform(function ($candidate) {
                    $candidate->phoneNumber = null;
                    return $candidate;
                });
            } else if ($result instanceof \Illuminate\Support\Collection) {
                $result->transform(function ($candidate) {
                    $candidate->phoneNumber = null;
                    return $candidate;
                });
            }
        }

        $candidates = Candidate::all();
        $currentYear = date('Y');

        $firstQuartal = "1" . "/" . $currentYear;

        foreach ($candidates as $candidate) {
            if($candidate->quartal){
                $candidateParts = explode('/', $candidate->quartal);
                $candidateQuartal = intval($candidateParts[0]); // Extract quartal
                $candidateYear = intval($candidateParts[1]); // Extract year

                // Check if candidate's year is earlier or if it's the same year but with a smaller quartal
                if ($candidateYear < $currentYear || ($candidateYear == $currentYear && $candidateQuartal < 1)) {
                    $firstQuartal = $candidate->quartal;
                    $currentYear = $candidateYear; // Update current year for future comparisons
                }
            }
        }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => $result,
                'firstQuartal' => $firstQuartal
            ]);

    }
}
