<?php

namespace App\Http\Controllers;

use App\Models\MonthCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $previousMonth = ($currentMonth - 1) > 0 ? ($currentMonth - 1) : 12;


        $last12Months = [];

        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::createFromDate($currentYear, $previousMonth, 1);

            // Go back by $i months
            $date = $date->subMonths($i);

            $formattedDate = $date->format('m/Y');

            $last12Months[] = $formattedDate;
        }


        return response()->json([
            'data' => $last12Months
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $company_id = $request->company_id;
        $monthNames = $request->monthName ?? [];
        $monthNumbers = $request->monthNumber ?? [];

        if (count($monthNames) === count($monthNumbers)) {
            $savedData = [];

            for ($i = 0; $i < count($monthNames); $i++) {
                $monthCompany = new MonthCompany();
                $monthCompany->company_id = $company_id;
                $monthCompany->monthName = $monthNames[$i];
                $monthCompany->monthNumber = $monthNumbers[$i];

                if ($monthCompany->save()) {
                    $savedData[] = $monthCompany;
                }
            }

            if (!empty($savedData)) {
                return response()->json([
                    'status' => 200,
                    'data' => $savedData
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Arrays have different lengths'
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MonthCompany  $monthCompany
     * @return \Illuminate\Http\Response
     */
    public function show(MonthCompany $monthCompany)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MonthCompany  $monthCompany
     * @return \Illuminate\Http\Response
     */
    public function edit(MonthCompany $monthCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MonthCompany  $monthCompany
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $monthCompany = MonthCompany::where('id', $id)->first();

        $monthCompany->monthName = $request->monthName;
        $monthCompany->monthNumber = $request->monthNumber;

        if ($monthCompany->save()) {
            return response()->json([
                'status' => 200,
                'data' => $monthCompany
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'data' => [],
                'message' => 'something went wrong with update monthCompany'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MonthCompany  $monthCompany
     * @return \Illuminate\Http\Response
     */
    public function destroy(MonthCompany $monthCompany)
    {
        //
    }
}
