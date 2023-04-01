<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LoginController;
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


Route::post('login', [LoginController::class, 'login']);

//Companies
Route::middleware('auth:sanctum')->group(function () {

Route::get('companies', [CompanyController::class, 'index']);
Route::post('company', [CompanyController::class, 'store']);
Route::get('company/{$id}', [CompanyController::class, 'show']);


//User

Route::post('storeUser', [LoginController::class, 'store']);

Route::post('logout', [LoginController::class, 'logout']);
Route::get('users', [LoginController::class, 'index']);

});