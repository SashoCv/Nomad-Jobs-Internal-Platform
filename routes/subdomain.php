<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::domain('dev.nomad-cloud.in')->group(function () {
    dd('Reached subdomain route');
    Route::get('searchCompany', [SearchController::class, 'searchCompany']);
});

     