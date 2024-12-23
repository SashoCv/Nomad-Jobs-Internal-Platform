<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class make_view_documents_for_company_user extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:make_view_documents_for_company_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make view documents for company user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $documentsThatNeedToBeViewed = DB::table('files')
        ->join('categories', 'files.category_id', '=', 'categories.id')
        ->select('files.id', 'files.fileName', 'files.category_id', 'categories.nameOfCategory')
        ->where('files.fileName', 'like', 'ТД%')
        ->orWhere('files.fileName','like','%passport%')
        ->orWhere(function($query) {
            $query->whereRaw('LOWER(categories.nameOfCategory) = LOWER(?)', ['ВИЗА']);
        })
        ->get();

        
    }
}
