<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\WorkerController;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Login

Route::post('login', [LoginController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {


    Route::get('user', [LoginController::class, 'user'])->name('user');


    //Companies
    Route::get('companies', [CompanyController::class, 'index']);
    Route::post('company', [CompanyController::class, 'store']);
    Route::get('company/{id}', [CompanyController::class, 'show']);
    Route::put('companyUpdate/{company}', [CompanyController::class, 'update']);
    Route::delete('companyDelete/{id}', [CompanyController::class, 'destroy']);


    //User

    Route::post('storeUser', [LoginController::class, 'store']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::get('users', [LoginController::class, 'index']);
    Route::put('userUpdate/{id}', [LoginController::class, 'update']);
    Route::delete('userDelete/{id}', [LoginController::class, 'destroy']);



    // Candidates, Worker
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('workers', [WorkerController::class, 'index']);
    Route::post('personSave', [CandidateController::class, 'store']);
    Route::get('person/{id}', [CandidateController::class, 'show']);
    Route::put('personUpdate/{id}', [CandidateController::class, 'update']);
    Route::delete('personDelete/{id}', [CandidateController::class, 'destroy']);
    Route::put('favoriteCandidate/{id}', [CandidateController::class, 'favoriteCandidate']);




    // Files
    Route::post('file', [FileController::class, 'store']);
    Route::get('downloadFile/{file}', [FileController::class, 'download']);
    Route::delete('fileDelete/{id}', [FileController::class, 'destroy']);


    // Search
    Route::get('searchName', [SearchController::class, 'searchName']);
    Route::get('searchCompany', [SearchController::class, 'searchCompany']);
    Route::get('searchStatus', [SearchController::class, 'searchStatus']);



    //Status

    Route::get('statuses', [StatusController::class, 'index']);
});
