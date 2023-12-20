<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthCompanyController;
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
    return view('test');
});


Route::get('searchCompany', [SearchController::class, 'searchCompany']);
Route::get('searchCandidateNew', [SearchController::class, 'searchCandidateNew']);

Route::get('printAllDocuments/{id}', [CompanyController::class, 'printAllDocuments']);

Route::get('downloadFile/{file}', [FileController::class, 'downloadFile']);
Route::get('candidate/{id}', [CandidateController::class, 'showPerson']);

Route::get('candidateNew/{id}', [CandidateController::class, 'showPersonNew']);

Route::get('admins', [LoginController::class, 'admins'])->name('admins');
Route::get('months', [MonthCompanyController::class, 'index']);
Route::get('downloadAllFile', [FileController::class, 'downloadAllFile']);



// Dva dokumenti za dodavanje od koi edniot e samo kandidatot i kompanijata
// drugiot e oglas za rabotata

// potpis i pecat da se dodava vo dokumentite t.e kaj kompanijata
// obuka za vanje potpis i pechat
// tabelata so meseci da se vnesuva preku aplikacijata
// potpis kaj admins



//Sredeno
// Pechatenje na site dokumenti za eden kandidat... -- SREDENO da mu gi prakjam site dokumenti
// downoad so fileName -- SREDENO
// na view na kandidatot da se gleda profil na kandidatot so negovite dokumenti -- SREDENO
// vo filterot da se gledaat kandidati spored toa na koj mu se dodeleni za podnesuvanje koj e opolnomoshten
// Dodavanje na potpisnik na dokumenti za kandidati za podavanje na dokumenti (add user for every candidate)
// Kompaniite nivnite kandidati da gi gledat so statusot do kaj im e rabotata da ima progres bar (malku shminka so boi spored status)
