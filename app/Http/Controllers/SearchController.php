<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{

    public function searchCandidate(Request $request)
    {
        if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2) {

            if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('company_id', '=', $request->searchCompany)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)
                    ->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {
                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('status_id', '=', $request->searchStatus)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $request->searchCompany)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            }
        } else {
            if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('company_id', '=', $request->searchCompany)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)
                    ->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', Auth::user()->company_id)
                    ->orderBy('date','DESC')
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {
                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('status_id', '=', $request->searchStatus)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status','position'])->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', Auth::user()->company_id)->orderBy('date','DESC')->get();

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
}
