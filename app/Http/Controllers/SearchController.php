<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentCandidateResource;
use App\Models\Statushistory;
use App\Traits\HasRolePermissions;
use App\Models\AgentCandidate;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\CompanyFile;
use App\Models\Status;
use App\Models\UserOwner;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    use HasRolePermissions;

    public function searchCandidate(Request $request)
    {
        if (!$this->checkPermission(Permission::CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $user = Auth::user();

        if ($this->isStaff()) {

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
            // Company users can only see their own company candidates
            $companyId = $user->company_id;

            if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('company_id', '=', $request->searchCompany)
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate == '') {

                $result = Candidate::with(['company', 'status', 'position'])->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)
                    ->where('company_id', '=', $companyId)
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
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate == '') {
                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus == '' && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('type_id', '=', 1)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('status_id', '=', $request->searchStatus)->where('type_id', '=', 1)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchStatus && $request->searchDate) {

                $result = Candidate::with(['company', 'status', 'position'])->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 1)->where('status_id', '=', $request->searchStatus)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            }
        }
    }

    public function searchWorker(Request $request)
    {
        if (!$this->checkPermission(Permission::CANDIDATES_READ)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $user = Auth::user();

        if ($this->isStaff()) {

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
            // Company users can only see their own company workers
            $companyId = $user->company_id;

            if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)
                    ->where('company_id', '=', $request->searchCompany)
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                $result = Candidate::with('company')->where('company_id', '=', $request->searchCompany)
                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchDate == '') {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)

                    ->where('fullName', 'LIKE', '%' . $request->searchName . '%')
                    ->where('company_id', '=', $companyId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate == '') {
                $result = Candidate::with('company')->where('type_id', '=', 2)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany == '' && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany == '' && $request->searchDate) {

                $result = Candidate::with('company')->where('type_id', '=', 2)->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName == '' && $request->searchCompany && $request->searchDate) {

                $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
            } else if ($request->searchName && $request->searchCompany && $request->searchDate) {

                $result = Candidate::with('company')->where('date', '=', $request->searchDate)->where('fullName', 'LIKE', '%' . $request->searchName . '%')->where('type_id', '=', 2)->where('company_id', '=', $request->searchCompany)->where('company_id', '=', $companyId)->get();

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'data' => $result
                ]);
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
        $cityId = $request->city_id;
        $perPage = $request->input('perPage', 10);

        $companiesQuery = Company::with(['industry', 'candidates','company_addresses', 'company_addresses.city']);
        $user = Auth::user();

        if($user->hasRole(Role::COMPANY_USER)){
            $companiesQuery->where('id', $user->company_id);
        }

        if ($user->hasRole(Role::COMPANY_OWNER)) {
            $companyOwner = UserOwner::where('user_id', $user->id)->get();
            $companyIds = $companyOwner->pluck('company_id');

            $companiesQuery->whereIn('id', $companyIds);
        }

        if ($EIK) {
            $companiesQuery->where('EIK', 'like', "$EIK%");
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

        if ($cityId) {
            $companiesQuery->whereHas('company_addresses', function ($query) use ($cityId) {
                $query->where('city_id', $cityId);
            });
        }


        $companies = $companiesQuery->orderBy('id', 'DESC')->paginate($perPage);

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
            'status',
            'company.company_addresses',
            'arrival.files',
            'activeContract'
        ])
            ->whereNotNull('status_id');

        $user = Auth::user();

        if ($user->hasRole(Role::COMPANY_USER)) {
            $query->where('company_id', $user->company_id);
        }

        if ($user->role_id == Role::AGENT) {
            $searchName = $request->searchName;
            $searchCompanyJob = $request->searchCompanyJob;
            $searchAgentStatus = $request->searchAgentStatus;
            $searchNationality = $request->searchNationality;
            $searchCreatedAt = $request->searchCreatedAt;

            $candidatesQuery = AgentCandidate::with(['candidate', 'companyJob', 'companyJob.company', 'statusForCandidateFromAgent', 'user'])
                ->where('agent_candidates.user_id', $user->id)
                ->where('agent_candidates.deleted_at', null)
                ->whereHas('candidate', function ($query) {
                    $query->whereNull('deleted_at');
                });

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
                $candidatesQuery->where('agent_candidates.company_job_id', '=', $searchCompanyJob);
            }

            if($searchAgentStatus){
                $candidatesQuery->where('status_for_candidate_from_agent_id', '=', $searchAgentStatus);
            }

            if($searchNationality){
                $candidatesQuery->whereHas('candidate', function ($subquery) use ($searchNationality) {
                    $subquery->where('nationality', 'LIKE', '%' . $searchNationality . '%');
                });
            }

            $perPage = $request->per_page ?? 20;
            $candidates = $candidatesQuery->paginate($perPage);
            return AgentCandidateResource::collection($candidates);
        }

        if ($user->role_id == Role::COMPANY_OWNER) {
            $companyOwner = UserOwner::where('user_id', $user->id)->get();
            $companyIds = $companyOwner->pluck('company_id');

            $query->whereIn('company_id', $companyIds);
        }


        \DB::enableQueryLog();

        if (!$searchEverything) {
            $searchName = $request->searchName;

            $query->when($searchName, function ($q) use ($searchName) {
                \Log::info('searchName: ' , [$searchName]);
                $q->where(function ($query) use ($searchName) {
                    $query->where('fullName', 'LIKE', '%' . $searchName . '%')
                        ->orWhere('fullNameCyrillic', 'LIKE', '%' . $searchName . '%');
                });
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
                    $q->where('status_id', $request->searchStatus);
                })
                ->when($request->searchDate, function ($q) use ($request) {
                    $q->whereHas('statusHistories', function ($query) use ($request) {
                        $query->whereDate('statusDate', $request->searchDate);
                    });
                })
                ->when($request->dossierNumber, function ($q) use ($request) {
                    $q->where('dossierNumber', '=', $request->dossierNumber);
                })
                ->when($request->contractType, function ($q) use ($request) {
                    $contractType = $request->contractType;
                    $map = [
                        'ЕРПР 1' => 'ЕРПР 1',
                        'ЕРПР 2' => 'ЕРПР 2',
                        'ЕРПР 3' => 'ЕРПР 3',
                        '90 дни' => '90 дни',
                        '9 месеца' => '9 месеца',
                    ];

                    $contractTypeLatin = $map[$contractType] ?? $contractType;
                    $q->where('contractType', '=', $contractTypeLatin);
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
                ->when($request->searchAgent, function ($q) use ($request) {
                    $q->where('agent_id', $request->searchAgent);
                    Log::info("here", [$request->searchAgent]);
                })
                ->when($request->user_id, function ($q) use ($request) {
                    $q->where('user_id', '=', $request->user_id);
                })->when($request->searchCity, function ($q) use ($request) {
                    $cityId = (int) $request->searchCity;
                    $q->whereHas('companyAddress', function ($query) use ($cityId) {
                        $query->where('city_id', $cityId);
                    });
                });


            // Провери дали има orderBy параметар за азбучно сортирање
            if ($request->orderBy === 'name_asc') {
                $result = $query->orderBy('fullName', 'ASC')->paginate(20);
            } elseif ($request->orderBy === 'name_desc') {
                $result = $query->orderBy('fullName', 'DESC')->paginate(20);
            } elseif ($request->searchCompany) {
                $result = $query->orderByDesc(
                    \App\Models\Statushistory::select('statusDate')
                        ->whereColumn('candidate_id', 'candidates.id')
                        ->orderByDesc('statusDate')
                        ->limit(1)
                )->paginate(20);
            } else {
                $result = $query->orderBy('id', 'DESC')->paginate(20);
            }
            \Log::info('SQL Query:', \DB::getQueryLog());
            $allStatuses = Status::all();

            foreach ($result as $candidate) {
                if($candidate->status_id){
                    $currentStatusId = $candidate->status_id;

                    $candidate->availableStatuses = $allStatuses->where('id', '!=', $currentStatusId)->pluck('id')->toArray();
                    
                    // Allow adding/editing if status is ARRIVAL_EXPECTED (18) OR if arrival already exists
                    $hasArrival = $candidate->arrival !== null;
                    $candidate->addArrival = ($currentStatusId == 18 || $hasArrival);
                } else {
                    $candidate->availableStatuses = $allStatuses->pluck('id')->toArray();
                    $candidate->addArrival = false;
                }
                $candidate->statusDate = $candidate->statusDate; // This will trigger the accessor
            }
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

            // Провери дали има orderBy параметар за азбучно сортирање
            if ($request->orderBy === 'name_asc') {
                $result = $query->orderBy('fullName', 'ASC')->get();
            } elseif ($request->orderBy === 'name_desc') {
                $result = $query->orderBy('fullName', 'DESC')->get();
            } else {
                $result = $query->orderBy('id', 'DESC')->get();
            }
        }

        if ($user->hasRole(Role::COMPANY_USER) || $user->hasRole(Role::COMPANY_OWNER)) {
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
