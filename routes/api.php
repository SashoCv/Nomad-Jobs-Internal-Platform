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
use App\Http\Controllers\ArrivalPricingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\ChangeLogController;
use App\Http\Controllers\StatushistoryController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\ContractPricingController;
use App\Http\Controllers\UserOwnerController;
use App\Http\Controllers\AsignCandidateToNomadOfficeController;
use App\Http\Controllers\InvoiceCompanyController;
use App\Http\Controllers\CasesController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ItemInvoiceController;
use App\Http\Controllers\InvoiceCompanyCandidateController;
use App\Http\Controllers\ArrivalCandidateController;
use App\Http\Controllers\StatusArrivalController;
use App\Http\Controllers\CashPaymentForCandidatesController;
use App\Http\Controllers\MigrationDocumentPreparationController;
use App\Http\Controllers\StatusForCandidateFromAgentController;
use App\Http\Controllers\MedicalInsuranceController;
use App\Http\Controllers\CompanyServiceContractController;
use App\Http\Controllers\ContractServiceTypeController;
use App\Http\Controllers\CompanyRequestController;
use App\Http\Controllers\StatisticController;
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
Route::get('downloadDocumentsForArrivalCandidate/{candidateId}', [ArrivalCandidateController::class, 'downloadDocumentsForArrivalCandidates']);
Route::get('downloadDocumentsForCandidatesFromAgent/{candidateId}', [AgentCandidateController::class, 'downloadDocumentsForCandidatesFromAgent']);


Route::middleware('auth:sanctum')->group(function () {


    Route::get('user', [LoginController::class, 'user'])->name('user');
    Route::get('roles', [LoginController::class, 'roles']);
    Route::get('rolesIdAndName', [LoginController::class, 'rolesIdAndName']);
    Route::get('admins', [LoginController::class, 'admins']);
    Route::post('changePasswordForUser', [LoginController::class, 'changePasswordForUser']);



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
    Route::post('extendContractForCandidate/{id}', [CandidateController::class, 'extendContractForCandidate']);
    Route::get('exportCandidates', [CandidateController::class, 'exportCandidates']); // new route for exporting candidates with latest status
    Route::get('exportCandidatesBasedOnStatus', [CandidateController::class, 'exportCandidatesBasedOnStatus']); // new route for exporting candidates based on status

    Route::get('contract-types', [CandidateController::class, 'types']);

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
    Route::delete('deleteCategory', [CategoryController::class, 'destroy']);


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
    Route::post('agentUpdateCandidate/{id}', [AgentCandidateController::class, 'updateCandidateAsAgent']);
    Route::get('getCandidatesForAssignedJob/{id}', [AgentCandidateController::class, 'getCandidatesForAssignedJob']);
    Route::get('getAllCandidatesFromAgents', [AgentCandidateController::class, 'getAllCandidatesFromAgents']);
    Route::get('statusForCandidateFromAgent', [StatusForCandidateFromAgentController::class, 'index']);
    Route::post('updateStatusForCandidateFromAgent/{id}', [StatusForCandidateFromAgentController::class, 'update']);
    Route::delete('deleteCandidateFromAgent/{id}', [AgentCandidateController::class, 'destroy']);

    Route::post('assignToAnotherJobPosting', [AssignedJobController::class, 'assignToAnotherJobPosting']); // New Route


    // Assign Candidates From agents to Nomad Offices for preparing documents
    Route::post('assignCandidateToNomadOffice', [AsignCandidateToNomadOfficeController::class, 'assignCandidateToNomadOffice']);
    Route::get('getCandidateFromAgent', [AsignCandidateToNomadOfficeController::class, 'index']);

    // Cases
    Route::get('getCases', [CasesController::class, 'index']);

    //CV For Candidates
    Route::get('getCvForCandidate', [CandidateController::class, 'generateCandidatePdf']);


    //Company Invoice (OLD Finance NEED TO BE DELETE AFTER NEW IMPLEMENTATION)
    Route::post('storeCompanyInvoice', [InvoiceCompanyController::class, 'store']);
    Route::delete('deleteCompanyInvoice/{id}', [InvoiceCompanyCandidateController::class, 'destroy']);
    Route::post('invoicePaid/{id}', [InvoiceCompanyController::class, 'invoicePaid']);
    Route::get('downloadExcelForInvoices', [InvoiceCompanyController::class, 'downloadExcelForInvoices']);
    Route::post('updateInvoice/{id}', [InvoiceCompanyController::class, 'update']);
    Route::get('invoiceCompanyCandidates', [InvoiceCompanyCandidateController::class, 'index']);
    Route::get('invoiceCompanyCandidates/{id}', [InvoiceCompanyCandidateController::class, 'show']);
    Route::get('filterAutoCompleteCandidateThatHaveInvoice', [InvoiceCompanyCandidateController::class, 'filterAutoCompleteCandidateThatHaveInvoice']);

    // Cash Payment For Candidates
    Route::post('storeCashPaymentForCandidates', [CashPaymentForCandidatesController::class, 'store']);
    Route::get('getCashPaymentForCandidates', [CashPaymentForCandidatesController::class, 'index']);
    Route::get('getCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'show']);
    Route::post('updateCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'update']);
    Route::delete('deleteCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'destroy']);
    //Items For Invoice
    Route::get('itemForInvoices', [ItemInvoiceController::class, 'index']);
    Route::post('updateItemForInvoice/{id}', [ItemInvoiceController::class, 'update']);
    Route::get('itemForCompanyInvoices', [ItemsForInvoicesController::class, 'index']);


    // INVOICES
    Route::get('getInvoices', [\App\Http\Controllers\InvoiceController::class, 'index']);
    Route::post('invoices/{id}/status', [\App\Http\Controllers\InvoiceController::class, 'store']);
    Route::delete('invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'destroy']);

    // Arrivals
    Route::post('storeArrival', [ArrivalController::class, 'store']); // i need statushistories mail also here
    Route::post('updateArrival/{id}', [ArrivalController::class, 'update']);
    Route::get('getAllArrivals', [ArrivalController::class, 'index']);
    Route::delete('deleteArrival/{id}', [ArrivalController::class, 'destroy']);
    Route::get('getArrivalCandidates', [ArrivalCandidateController::class, 'getArrivalCandidates']); // NEW ROUTE



    // Status Arrivals
    Route::get('getStatusArrivals', [StatusArrivalController::class, 'index']);

    // Candidates for Arrivals
    Route::post('storeStatusForArrivalCandidate', [ArrivalCandidateController::class, 'store']);
    Route::get('getArrivalCandidatesWithStatuses', [ArrivalCandidateController::class, 'index']);
    Route::post('updateStatusForArrivalCandidate/{id}', [ArrivalCandidateController::class, 'update']);
    Route::delete('deleteArrivalCandidate/{id}', [ArrivalCandidateController::class, 'destroy']);


    // Candidates whose contracts are expiring
    Route::get('getCandidatesWhoseContractsAreExpiring', [CandidateController::class, 'getCandidatesWhoseContractsAreExpiring']);


    // Medical Insurance
    Route::post('storeMedicalInsuranceForCandidate', [MedicalInsuranceController::class, 'store']);
    Route::get('getMedicalInsuranceForCandidates', [MedicalInsuranceController::class, 'show']);
    Route::get('getMedicalInsurance/{id}', [MedicalInsuranceController::class, 'showForCandidate']);
    Route::get('getMedicalInsuranceForCandidate/{id}', [MedicalInsuranceController::class, 'index']);
    Route::post('updateMedicalInsurance/{id}', [MedicalInsuranceController::class, 'update']);
    Route::delete('deleteMedicalInsurance/{id}', [MedicalInsuranceController::class, 'destroy']);


    // MigrationDocumentPreparation
    Route::post('storeMigrationDocumentPreparation', [MigrationDocumentPreparationController::class, 'store']);
    Route::get('getMigrationDocumentPreparation', [MigrationDocumentPreparationController::class, 'index']);
    Route::get('getMigrationDocumentPreparation/{id}', [MigrationDocumentPreparationController::class, 'show']);
    Route::post('updateMigrationDocumentPreparation/{id}', [MigrationDocumentPreparationController::class, 'update']);
    Route::delete('deleteMigrationDocumentPreparation/{id}', [MigrationDocumentPreparationController::class, 'destroy']);
    Route::get('exportMigrationDocumentPreparation', [MigrationDocumentPreparationController::class, 'export']);



    // Statistics DASHBOARD
    Route::get('statistics', [StatisticController::class, 'statistics']);


    // Company Service Contract
    Route::post('storeCompanyServiceContract', [CompanyServiceContractController::class, 'store']);
    Route::post('storeContractFileForCompany', [CompanyServiceContractController::class, 'storeContractFileForCompany']);
    Route::get('downloadContractFile/{companyId}', [CompanyServiceContractController::class, 'downloadContractFile']);
    Route::get('getCompanyServiceContracts', [CompanyServiceContractController::class, 'index']);
    Route::get('getCompanyServiceContract/{id}', [CompanyServiceContractController::class, 'show']);
    Route::post('updateCompanyServiceContract/{id}', [CompanyServiceContractController::class, 'update']);
    Route::delete('deleteCompanyServiceContract/{id}', [CompanyServiceContractController::class, 'destroy']);
    Route::delete('deleteContractFile/{id}', [CompanyServiceContractController::class, 'deleteContractFile']);
    // Contract Service Types
    Route::get('getContractServiceTypes', [ContractServiceTypeController::class, 'index']);

    // Contract Pricing
    Route::post('storeContractPricing', [ContractPricingController::class, 'store']);
    Route::put('updateContractPricing/{id}', [ContractPricingController::class, 'update']);
    Route::get('getContractPricing/{id}', [ContractPricingController::class, 'show']);
    Route::delete('deleteContractPricing/{id}', [ContractPricingController::class, 'destroy']);


    // Company Requests
    Route::get('companyRequests', [CompanyRequestController::class, 'index']);
    Route::get('showPriceForCompanyBasedOnRequest/{id}', [CompanyRequestController::class, 'showPriceBasedOnRequest']);

    Route::post('approveCompanyRequest/{id}', [CompanyRequestController::class, 'approveCompanyRequest']);
    Route::post('rejectCompanyRequest/{id}', [CompanyRequestController::class, 'rejectCompanyRequest']);


    // Change Logs
    Route::post('storeChangeLog', [ChangeLogController::class, 'store']);
    Route::get('changeLogs', [ChangeLogController::class, 'index']);
    Route::post('approveLog/{id}', [ChangeLogController::class, 'approveLog']);
    Route::post('approveChangeLog/{id}', [ChangeLogController::class, 'approveChangeLog']);
    Route::post('declineChangeLog/{id}', [ChangeLogController::class, 'rejectChangeLog']);
    Route::delete('deleteChangeLog/{id}', [ChangeLogController::class, 'destroy']);


    // TRANSPORT FOR CANDIDATES
    Route::get('getTransportForCandidates', [ArrivalCandidateController::class, 'getTransportForCandidates']);
    Route::post('storePricingForArrival', [ArrivalPricingController::class, 'store']);
    Route::post('storeInvoiceForArrivalCandidate', [ArrivalPricingController::class, 'storeInvoiceForArrivalCandidate']); // need implementation in finance, and after that here we need logic
    Route::post('storeTransportCoverBy/{arrivalId}', [ArrivalPricingController::class, 'storeTransportCoverBy']);


});

