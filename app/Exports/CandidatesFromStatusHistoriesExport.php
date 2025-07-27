<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CandidatesFromStatusHistoriesExport implements FromView
{
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }


    public function view(): View
    {
        return view('candidatesFromStatusHistoriesExport', [
            'allData' => $this->data
        ]);
    }
}
