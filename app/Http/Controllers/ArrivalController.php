<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailForArrivalCandidates;
use App\Jobs\SendEmailForArrivalStatusCandidates;
use App\Services\InvoiceService;
use App\Traits\HasRolePermissions;
use App\Jobs\SendEmailToCompany;
use App\Models\Arrival;
use App\Models\ArrivalCandidate;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ArrivalController extends Controller
{
    use HasRolePermissions;
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            if($this->isStaff()) {
                $arrivals = Arrival::with(['company', 'candidate'])->get();
            } else {
                $arrivals = []; // Here i need to implement the logic to get the arrivals for the Company
            }

            return response()->json([
                'message' => 'Arrivals retrieved successfully',
                'arrivals' => $arrivals
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
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
        try {
            DB::beginTransaction();

            $candidateId = $request->candidate_id;

            $arrival = Arrival::firstOrNew(['candidate_id' => $candidateId]);
            $arrivalDate = Carbon::createFromFormat('m-d-Y', $request->arrival_date)->format('Y-m-d');

            $arrival->fill([
                'company_id'       => $request->company_id,
                'arrival_date'     => $arrivalDate,
                'arrival_time'     => $request->arrival_time,
                'arrival_location' => $request->arrival_location,
                'arrival_flight'   => $request->arrival_flight,
                'where_to_stay'    => $request->where_to_stay,
                'phone_number'     => $request->phone_number,
            ])->save();

            // Status ID for "Arrival Expected"
            $statusId = 18;
            $sendEmail = $request->sendEmail ?? false;

            Statushistory::create([
                'candidate_id' => $candidateId,
                'status_id'    => $statusId,
                'statusDate'   => $arrivalDate,
                'description'  => 'Arrival Expected',
            ]);

            InvoiceService::saveInvoiceOnStatusChange($candidateId, $statusId, $arrivalDate);

            // Ensure category exists
            Category::firstOrCreate(
                [
                    'candidate_id'     => $candidateId,
                    'nameOfCategory'   => 'Documents For Arrival Candidates',
                ],
                [
                    'role_id'      => 2,
                    'isGenerated'  => 0,
                ]
            );

            if($sendEmail){
                dispatch(new SendEmailForArrivalStatusCandidates($statusId, $candidateId, $arrivalDate));
                Log::info('send arrival information email for candidate ID: ' . $candidateId);
            }

            DB::commit();

            return response()->json([
                'message' => 'Arrival created successfully',
                'arrival' => $arrival,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!in_array(Auth::user()->role_id, [1, 2])) {
            return response()->json(['error' => 'You are not authorized to update an arrival'], 403);
        }

        try {
            DB::beginTransaction();

            $arrival = Arrival::findOrFail($id);
            $candidateId = $arrival->candidate_id;

            $arrivalDate = Carbon::createFromFormat('m-d-Y', $request->arrival_date)->format('Y-m-d');

            // Update Arrival fields
            $arrival->update([
                'company_id'       => $request->company_id,
                'arrival_date'     => $arrivalDate,
                'arrival_time'     => $request->arrival_time,
                'arrival_location' => $request->arrival_location,
                'arrival_flight'   => $request->arrival_flight,
                'where_to_stay'    => $request->where_to_stay,
                'phone_number'     => $request->phone_number,
            ]);

            // Status ID for "Arrival Expected"
            $statusId = 18;

            // Create or update status history
            Statushistory::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'status_id'    => $statusId,
                    'statusDate'   => $arrivalDate,
                ],
                [
                    'description' => 'Arrival Expected',
                ]
            );

            // Ensure category exists
            Category::updateOrCreate(
                [
                    'candidate_id'   => $candidateId,
                    'nameOfCategory' => 'Documents For Arrival Candidates',
                ],
                [
                    'role_id'     => 2,
                    'isGenerated' => 0,
                ]
            );

            dispatch(new SendEmailForArrivalStatusCandidates($statusId, $candidateId, $arrivalDate));

            DB::commit();

            return response()->json([
                'message' => 'Arrival updated successfully',
                'arrival' => $arrival,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function destroy($id): JsonResponse
    {
        if ($this->isStaff()) {
            try {
                $arrivalCandidates = ArrivalCandidate::where('arrival_id', $id)->first();
                $arrivalCandidates->delete();

                $arrival = Arrival::find($id);
                $arrival->delete();

                return response()->json('Arrival deleted successfully');
            } catch (\Exception $e) {
                return response()->json($e->getMessage());
            }
        } else {
            return response()->json('You are not authorized to delete this arrival');
        }
    }
}
