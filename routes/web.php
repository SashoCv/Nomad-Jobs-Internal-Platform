<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthCompanyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CompanyController;


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

// Route::get('scriptForPassportDelete', [FileController::class, 'index']);

// Route::get('scriptForPassport', [CandidateController::class, 'script']);


// Route::get('company/{id}', [CompanyController::class, 'show']);
//     Route::post('companyStore', [CompanyController::class, 'store']);