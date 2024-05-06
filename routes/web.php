<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthCompanyController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// Route::get('searchCompany', [SearchController::class, 'searchCompany']);
// Route::post('storePosition', [CompanyJobController::class, 'store']);

// Route::get('searchCandidateNew', [SearchController::class, 'searchCandidateNew']);

// Route::get('printAllDocuments/{id}', [CompanyController::class, 'printAllDocuments']);

// Route::get('downloadFile/{file}', [FileController::class, 'downloadFile']);
// Route::get('candidate/{id}', [CandidateController::class, 'showPerson']);

// Route::get('candidateNew/{id}', [CandidateController::class, 'showPersonNew']);

// Route::get('admins', [LoginController::class, 'admins'])->name('admins');
// Route::get('months', [MonthCompanyController::class, 'index']);
// Route::get('downloadAllFile/{id}', [FileController::class, 'downloadAllFile']);


// Route::get('deleteDocumentForPosition/{id}', [PositionController::class, 'destroyDocumentForPosition']);


// Route::get('scriptForPassport', [CandidateController::class, 'script']);

// Route::get('scriptForPassportDelete', [FileController::class, 'index']);

Route::get('generateCandidatePdf', [CandidateController::class, 'generateCandidatePdf']);

// Route::get('addQuartalToAllCandidates', [CandidateController::class, 'addQuartalToAllCandidates']);

// Route::get('getFirstQuartal', [CandidateController::class, 'getFirstQuartal']);

Route::get('documentsThatCanBeViewedByCompany', [FileController::class, 'index']);

// Route::get('scriptForAddedBy', [CandidateController::class, 'scriptForAddedBy']);

Route::get('scriptForSeasonal', [CandidateController::class, 'scriptForSeasonal']);
