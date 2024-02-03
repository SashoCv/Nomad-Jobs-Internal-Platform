<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('searchCompany', [SearchController::class, 'searchCompany']);
// Add other subdomain-specific routes here if needed
