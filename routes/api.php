<?php

use App\Http\Controllers\AgentCandidateController;
use App\Http\Controllers\AssignedJobController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyCategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyFileController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthCompanyController;
use App\Http\Controllers\ItemsForInvoicesController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StatushistoryController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\UserOwnerController;
use App\Http\Controllers\AsignCandidateToNomadOfficeController;
use App\Http\Controllers\InvoiceCompanyController;
use App\Http\Controllers\CasesController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ItemInvoiceController;
use App\Http\Controllers\InvoiceCompanyCandidateController;
use App\Http\Controllers\ArrivalCandidateController;
use App\Http\Controllers\StatusArrivalController;
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
Route::get('test', [CompanyController::class, 'test']);
Route::get('downloadAllFile/{id}', [FileController::class, 'downloadAllFile']);


Route::middleware('auth:sanctum')->group(function () {


    Route::get('user', [LoginController::class, 'user'])->name('user');
    Route::get('admins', [LoginController::class, 'admins']);



    //Companies
    Route::get('companies', [CompanyController::class, 'index']);
    Route::post('companyStore', [CompanyController::class, 'store']);
    Route::get('company/{id}', [CompanyController::class, 'show']); //change
    Route::post('companyUpdate/{id}', [CompanyController::class, 'update']);
    Route::delete('companyDelete/{id}', [CompanyController::class, 'destroy']);
    Route::post('companyMonthStore', [MonthCompanyController::class, 'store']); // new
    Route::post('companyMonthUpdate/{id}', [MonthCompanyController::class, 'update']); // new
    Route::get('months', [MonthCompanyController::class, 'index']); // new

    Route::get('allCompaniesWithIdAndName', [CompanyController::class, 'allCompaniesWithIdAndName']);





    //User

    Route::post('storeUser', [LoginController::class, 'store']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::get('users', [LoginController::class, 'index']);
    Route::get('user/{id}', [LoginController::class, 'show']);
    Route::post('userUpdate/{id}', [LoginController::class, 'update']);
    Route::delete('userDelete/{id}', [LoginController::class, 'destroy']);



    // Candidates, Worker
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('candidate/{id}', [CandidateController::class, 'showPerson']);
    Route::get('employees', [CandidateController::class, 'employees']);
    Route::post('personSave', [CandidateController::class, 'store']);
    Route::get('person/{id}', [CandidateController::class, 'show']);
    Route::post('personUpdate/{id}', [CandidateController::class, 'update']);
    Route::delete('personDelete/{id}', [CandidateController::class, 'destroy']);
    Route::post('favoriteCandidate', [FavoriteController::class, 'store']);
    Route::get('favoriteCandidates/{id}', [FavoriteController::class, 'index']);
    Route::post('candidateToWorker/{id}', [CandidateController::class, 'worker']);
    Route::get('candidateNew/{id}', [CandidateController::class, 'showPersonNew']);
    Route::get('getCandidatesForCompany/{id}', [CandidateController::class, 'getCandidatesForCompany']);




    // Files
    Route::post('file', [FileController::class, 'store']);
    Route::get('downloadFile/{file}', [FileController::class, 'download']);
    Route::get('filesForPerson/{id}', [FileController::class, 'show']);
    Route::delete('fileDelete/{id}', [FileController::class, 'destroy']);



    //CompanyFiles
    Route::post('companyFileStore', [CompanyFileController::class, 'store']);
    Route::get('companyFile/{id}', [CompanyFileController::class, 'show']);
    Route::get('downloadCompanyFile/{file}', [CompanyFileController::class, 'download']);
    Route::delete('companyFileDelete/{id}', [CompanyFileController::class, 'destroy']);


    //Company Category

    Route::post('companyCategoryStore', [CompanyCategoryController::class, 'store']);
    Route::post('deleteCompanyCategory', [CompanyCategoryController::class, 'destroy']);





    // Search
    Route::get('searchCompany', [SearchController::class, 'searchCompany']);
    Route::get('searchDocuments', [SearchController::class, 'searchDocuments']);
    Route::get('searchCandidate', [SearchController::class, 'searchCandidate']);
    Route::get('searchCandidateNew', [SearchController::class, 'searchCandidateNew']);
    Route::get('searchWorker', [SearchController::class, 'searchWorker']);



    //Status

    Route::get('statuses', [StatusController::class, 'index']);
    Route::post('updateStatusForCandidate', [StatusController::class, 'updateStatusForCandidate']);


    // Categories

    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('storeCategory', [CategoryController::class, 'store']);
    Route::post('deleteCategory', [CategoryController::class, 'destroy']);


    // Status History

    Route::get('statusHistory/{id}', [StatushistoryController::class, 'show']);
    Route::post('statusHistory', [StatushistoryController::class, 'store']);
    Route::delete('deleteStatusHistory/{id}', [StatushistoryController::class, 'destroy']);


    // Job Positions

    Route::get('allPositions', [PositionController::class, 'index']);
    Route::post('storePosition', [PositionController::class, 'store']);
    Route::post('updatePosition/{id}', [PositionController::class, 'update']);
    Route::delete('deletePosition/{id}', [PositionController::class, 'destroy']);
    Route::delete('deleteDocumentForPosition/{id}', [PositionController::class, 'destroyDocumentForPosition']);




    // Industries

    Route::get('allIndustries', [IndustryController::class, 'index']);
    Route::post('storeIndustry', [IndustryController::class, 'store']);
    Route::post('updateIndustry/{id}', [IndustryController::class, 'update']);
    Route::delete('deleteIndustry/{id}', [IndustryController::class, 'destroy']);


    // Jobs for Companies
    Route::post('storeJobPosting', [CompanyJobController::class, 'store']);
    Route::get('jobPostings/{id}', [CompanyJobController::class, 'show']);
    Route::get('allJobPostings', [CompanyJobController::class, 'index']);  // need Function
    Route::post('updateJobPosting/{id}', [CompanyJobController::class, 'update']); // need Function
    Route::delete('deleteJobPosting/{id}', [CompanyJobController::class, 'destroy']); // need Function

    Route::delete('hardDeleteJobPosting/{id}', [CompanyJobController::class, 'hardDelete']); // suggested for next version

    // Notifications

    Route::get('notifications', [UserNotificationController::class, 'show']); // notification for user
    Route::post('seenNotifications', [UserNotificationController::class, 'update']); // update notification is_read for user need Function To change
    Route::post('readNotification/{id}', [UserNotificationController::class, 'readNotification']); // update notification is_seen for user


    // Assigned Jobs
    Route::post('assignJobToAgent', [AssignedJobController::class, 'store']);
    Route::get('getAgents', [AssignedJobController::class, 'getAgents']);
    Route::get('getAssignedJobs', [AssignedJobController::class, 'getAssignedJobs']);
    Route::delete('deleteAssignedJob/{id}', [AssignedJobController::class, 'deleteAssignedJob']);
    Route::get('getAssignedJobsForAgent', [AssignedJobController::class, 'getAssignedJobsForAgent']);

    // Company Owner
    Route::post('updateCompanyOwner/{id}', [UserOwnerController::class, 'update']);

    // Agents
    Route::post('agentAddCandidateForAssignedJob', [AgentCandidateController::class, 'agentAddCandidateForAssignedJob']);
    Route::get('getCandidatesForAssignedJob/{id}', [AgentCandidateController::class, 'getCandidatesForAssignedJob']);
    Route::get('getAllCandidatesFromAgents', [AgentCandidateController::class, 'getAllCandidatesFromAgents']);

    // Assign Candidates From agents to Nomad Offices for preparing documents
    Route::post('assignCandidateToNomadOffice', [AsignCandidateToNomadOfficeController::class, 'assignCandidateToNomadOffice']);
    Route::get('getCandidateFromAgent', [AsignCandidateToNomadOfficeController::class, 'index']);

    // Cases
    Route::get('getCases', [CasesController::class, 'index']);

    //CV For Candidates
    Route::get('getCvForCandidate', [CandidateController::class, 'generateCandidatePdf']);


    //Company Invoice
    Route::post('storeCompanyInvoice', [InvoiceCompanyController::class, 'store']);
//    Route::get('getCompanyInvoices', [InvoiceCompanyController::class, 'index']);
    Route::delete('deleteCompanyInvoice/{id}', [InvoiceCompanyCandidateController::class, 'destroy']);
    Route::post('invoicePaid/{id}', [InvoiceCompanyController::class, 'invoicePaid']);
    Route::get('downloadExcelForInvoices', [InvoiceCompanyController::class, 'downloadExcelForInvoices']);
//    Route::get('getCompanyInvoices/{id}', [InvoiceCompanyController::class, 'show']);
    Route::post('updateInvoice/{id}', [InvoiceCompanyController::class, 'update']);
    Route::get('invoiceCompanyCandidates', [InvoiceCompanyCandidateController::class, 'index']);
    Route::get('invoiceCompanyCandidates/{id}', [InvoiceCompanyCandidateController::class, 'show']);


    //Items For Invoice
    Route::get('itemForInvoices', [ItemInvoiceController::class, 'index']);
    Route::post('updateItemForInvoice/{id}', [ItemInvoiceController::class, 'update']);
    Route::get('itemForCompanyInvoices', [ItemsForInvoicesController::class, 'index']);


    // Arrivals
    Route::post('storeArrival', [ArrivalController::class, 'store']);
    Route::get('getAllArrivals', [ArrivalController::class, 'index']);
    Route::delete('deleteArrival/{id}', [ArrivalController::class, 'destroy']);


    // Status Arrivals
    Route::get('getStatusArrivals', [StatusArrivalController::class, 'index']);

    // Candidates for Arrivals
    Route::post('storeStatusForArrivalCandidate', [ArrivalCandidateController::class, 'store']);
    Route::get('getArrivalCandidatesWithStatuses', [ArrivalCandidateController::class, 'index']);
    Route::post('updateStatusForArrivalCandidate/{id}', [ArrivalCandidateController::class, 'update']);
    Route::delete('deleteArrivalCandidate/{id}', [ArrivalCandidateController::class, 'destroy']);
});
