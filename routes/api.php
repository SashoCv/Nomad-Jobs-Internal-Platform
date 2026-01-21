<?php

use App\Http\Controllers\AgentCandidateController;
use App\Http\Controllers\AssignedJobController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyCategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyFileController;
use App\Http\Controllers\CompanyJobController;
use App\Http\Controllers\JobPostingsOverviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonthCompanyController;
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
use App\Http\Controllers\CasesController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ArrivalCandidateController;
use App\Http\Controllers\StatusArrivalController;
use App\Http\Controllers\CashPaymentForCandidatesController;
use App\Http\Controllers\MigrationDocumentPreparationController;
use App\Http\Controllers\StatusForCandidateFromAgentController;
use App\Http\Controllers\MedicalInsuranceController;
use App\Http\Controllers\CompanyServiceContractController;
use App\Http\Controllers\ContractServiceTypeController;
use App\Http\Controllers\AgentServiceContractController;
use App\Http\Controllers\AgentContractPricingController;
use App\Http\Controllers\AgentServiceTypeController;
use App\Http\Controllers\CompanyRequestController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\CandidateVisaController;
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

/*
|--------------------------------------------------------------------------
| Public Routes - No Authentication Required
|--------------------------------------------------------------------------
*/

// Sanctum CSRF cookie route
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Login route (public)
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login');

// Password Reset Routes (Public)
Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword'])
    ->middleware('throttle:5,1')
    ->name('password.forgot');

Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
    ->name('password.reset');

Route::get('validate-reset-token', [PasswordResetController::class, 'validateToken'])
    ->middleware('throttle:10,1')
    ->name('password.validate-token');

/*
|--------------------------------------------------------------------------
| Protected Routes - Require Sanctum Authentication
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Authentication endpoints
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Impersonation endpoints (specific routes must come before dynamic {user} route)
    Route::post('impersonate/stop', [ImpersonationController::class, 'stop']);
    Route::get('impersonate/status', [ImpersonationController::class, 'status']);
    Route::post('impersonate/{user}', [ImpersonationController::class, 'start']);

    // Public routes (moved to protected)
    Route::get('test', [CompanyController::class, 'test']);
    Route::get('downloadAllFile/{id}', [FileController::class, 'downloadAllFile']);
    Route::get('downloadDocumentsForArrivalCandidate/{candidateId}', [ArrivalCandidateController::class, 'downloadDocumentsForArrivalCandidates']);
    Route::get('downloadDocumentsForCandidatesFromAgent/{candidateId}', [AgentCandidateController::class, 'downloadDocumentsForCandidatesFromAgent']);

    // User management
    Route::get('roles', [LoginController::class, 'roles']);
    Route::get('rolesIdAndName', [LoginController::class, 'rolesIdAndName']);
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
    Route::get('users', [LoginController::class, 'index']);
    Route::get('user/{id}', [LoginController::class, 'show']);
    Route::post('userUpdate/{id}', [LoginController::class, 'update']);
    Route::delete('userDelete/{id}', [LoginController::class, 'destroy']);
    Route::post('resendInvitation/{id}', [LoginController::class, 'resendInvitation']);



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
    Route::get('downloadFile/{file}', [FileController::class, 'downloadFile']);
    Route::get('filesForPerson/{id}', [FileController::class, 'show']);
    Route::put('files/{id}', [FileController::class, 'update']);
    Route::post('files/{id}/duplicate', [FileController::class, 'duplicate']);
    Route::post('documents/share', [\App\Http\Controllers\DocumentShareController::class, 'share']);
    Route::post('documents/download-selected', [FileController::class, 'downloadSelected']);
    Route::post('documents/bulk-delete', [FileController::class, 'bulkDestroy']);


    Route::delete('fileDelete/{id}', [FileController::class, 'destroy']);



    //CompanyFiles
    Route::post('companyFileStore', [CompanyFileController::class, 'store']);
    Route::get('companyFile/{id}', [CompanyFileController::class, 'show']);
    Route::put('companyFiles/{id}', [CompanyFileController::class, 'update']);
    Route::post('companyFiles/{id}/duplicate', [CompanyFileController::class, 'duplicate']);
    Route::get('downloadCompanyFile/{file}', [CompanyFileController::class, 'download']);
    Route::delete('companyFileDelete/{id}', [CompanyFileController::class, 'destroy']);


    //Company Category

    Route::post('companyCategoryStore', [CompanyCategoryController::class, 'store']);
    Route::put('companyCategoryUpdate/{id}', [CompanyCategoryController::class, 'update']);
    Route::delete('deleteCompanyCategory', [CompanyCategoryController::class, 'destroy']);





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
    Route::post('updateCategory/{category}', [CategoryController::class, 'update']);
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
    Route::patch('job-postings/{id}/status', [CompanyJobController::class, 'updateStatus']);

    // Job Posting Revisions
    Route::get('job-postings/{id}/revision', [CompanyJobController::class, 'getRevision']);
    Route::post('job-postings/{id}/revision/approve', [CompanyJobController::class, 'approveRevision']);
    Route::post('job-postings/{id}/revision/reject', [CompanyJobController::class, 'rejectRevision']);

    // Job Postings Overview
    Route::get('job-postings-overview', [JobPostingsOverviewController::class, 'index']);
    Route::get('job-postings-overview/{companyId}', [JobPostingsOverviewController::class, 'show']);

    // Notifications
    Route::get('notifications', [UserNotificationController::class, 'show']);
    Route::get('notifications/paginated', [UserNotificationController::class, 'index']);
    Route::post('seenNotifications', [UserNotificationController::class, 'update']);
    Route::post('readNotification/{id}', [UserNotificationController::class, 'readNotification']);
    Route::post('notifications/mark-all-read', [UserNotificationController::class, 'markAllAsRead']);

    // Calendar Events
    Route::get('calendar-events', [CalendarEventController::class, 'index']);

    // Assigned Jobs
    Route::post('assignJobToAgent', [AssignedJobController::class, 'store']);
    Route::post('bulkAssignJobsToAgent', [AssignedJobController::class, 'bulkAssign']);
    Route::post('assignMultipleAgentsToJob', [AssignedJobController::class, 'assignMultipleAgentsToJob']);
    Route::post('removeAgentFromJob', [AssignedJobController::class, 'removeAgentFromJob']);
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

    // Agent Candidate Details
    Route::get('agentCandidateDetails/{agentCandidateId}', [AgentCandidateController::class, 'getDetails']);
    Route::post('agentCandidateDetails/{agentCandidateId}', [AgentCandidateController::class, 'upsertDetails']);

    Route::post('assignToAnotherJobPosting', [AssignedJobController::class, 'assignToAnotherJobPosting']); // New Route


    // Assign Candidates From agents to Nomad Offices for preparing documents
    Route::post('assignCandidateToNomadOffice', [AsignCandidateToNomadOfficeController::class, 'assignCandidateToNomadOffice']);
    Route::get('getCandidateFromAgent', [AsignCandidateToNomadOfficeController::class, 'index']);
    Route::put('updateApprovedCandidateHRData/{id}', [AsignCandidateToNomadOfficeController::class, 'updateHRData']);

    // Cases
    Route::get('getCases', [CasesController::class, 'index']);

    //CV For Candidates
    Route::get('getCvForCandidate', [CandidateController::class, 'generateCandidatePdf']);



    // Cash Payment For Candidates
    Route::post('storeCashPaymentForCandidates', [CashPaymentForCandidatesController::class, 'store']);
    Route::get('getCashPaymentForCandidates', [CashPaymentForCandidatesController::class, 'index']);
    Route::get('getCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'show']);
    Route::post('updateCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'update']);
    Route::delete('deleteCashPaymentForCandidates/{id}', [CashPaymentForCandidatesController::class, 'destroy']);


    // INVOICES
    Route::get('getInvoices', [\App\Http\Controllers\InvoiceController::class, 'index']);
    Route::post('invoices/{id}/status', [\App\Http\Controllers\InvoiceController::class, 'store']);
    Route::delete('invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'destroy']);
    Route::get('exportInvoices', [\App\Http\Controllers\InvoiceController::class, 'exportInvoices']);

    // AGENT INVOICES
    Route::get('agent-invoices', [\App\Http\Controllers\AgentInvoiceController::class, 'index']);
    Route::put('agent-invoices/{id}', [\App\Http\Controllers\AgentInvoiceController::class, 'update']);
    Route::delete('agent-invoices/{id}', [\App\Http\Controllers\AgentInvoiceController::class, 'destroy']);

    // AGENT SERVICE TYPES (Reference Data)
    Route::get('agent-service-types', [\App\Http\Controllers\AgentServiceTypeController::class, 'index']);
    Route::post('agent-service-types', [\App\Http\Controllers\AgentServiceTypeController::class, 'store']);
    Route::put('agent-service-types/{id}', [\App\Http\Controllers\AgentServiceTypeController::class, 'update']);
    Route::delete('agent-service-types/{id}', [\App\Http\Controllers\AgentServiceTypeController::class, 'destroy']);

    // CONTRACT SERVICE TYPES (Reference Data)
    Route::get('contract-service-types', [\App\Http\Controllers\ContractServiceTypeController::class, 'index']);
    Route::post('contract-service-types', [\App\Http\Controllers\ContractServiceTypeController::class, 'store']);
    Route::put('contract-service-types/{id}', [\App\Http\Controllers\ContractServiceTypeController::class, 'update']);
    Route::delete('contract-service-types/{id}', [\App\Http\Controllers\ContractServiceTypeController::class, 'destroy']);

    // AGENT CANDIDATE STATUSES (Reference Data)
    Route::get('reference-data/agent-candidate-statuses', [ReferenceDataController::class, 'getAgentCandidateStatuses']);
    Route::post('reference-data/agent-candidate-statuses', [ReferenceDataController::class, 'storeAgentCandidateStatus']);
    Route::put('reference-data/agent-candidate-statuses/{id}', [ReferenceDataController::class, 'updateAgentCandidateStatus']);
    Route::delete('reference-data/agent-candidate-statuses/{id}', [ReferenceDataController::class, 'deleteAgentCandidateStatus']);

    // CANDIDATE STATUSES (Reference Data)
    Route::get('reference-data/candidate-statuses', [ReferenceDataController::class, 'getCandidateStatuses']);
    Route::post('reference-data/candidate-statuses', [ReferenceDataController::class, 'storeCandidateStatus']);
    Route::put('reference-data/candidate-statuses/{id}', [ReferenceDataController::class, 'updateCandidateStatus']);
    Route::delete('reference-data/candidate-statuses/{id}', [ReferenceDataController::class, 'deleteCandidateStatus']);

    // Arrivals
    Route::post('storeArrival', [ArrivalController::class, 'store']); // i need statushistories mail also here
    Route::put('updateArrival/{id}', [ArrivalController::class, 'update']);
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


    // Candidate Visas
    Route::post('candidate-visas', [CandidateVisaController::class, 'store']);
    Route::get('candidate-visas/{candidateId}', [CandidateVisaController::class, 'show']);
    Route::get('candidate-visas/{candidateId}/history', [CandidateVisaController::class, 'history']);
    Route::put('candidate-visas/{id}', [CandidateVisaController::class, 'update']);
    Route::delete('candidate-visas/{id}', [CandidateVisaController::class, 'destroy']);
    Route::get('candidate-visas/{id}/download', [CandidateVisaController::class, 'download']);
    Route::get('candidate-visas/{id}/view', [CandidateVisaController::class, 'view']);


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
    Route::get('agents-statistics', [StatisticController::class, 'agentsStatistics']);


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

    // Agent Service Contract
    Route::get('getAgentServiceContracts', [AgentServiceContractController::class, 'index']);
    Route::get('getAgentServiceContract/{id}', [AgentServiceContractController::class, 'show']);
    Route::post('storeAgentServiceContract', [AgentServiceContractController::class, 'store']);
    Route::post('updateAgentServiceContract/{id}', [AgentServiceContractController::class, 'update']);
    Route::delete('deleteAgentServiceContract/{id}', [AgentServiceContractController::class, 'destroy']);
    Route::post('storeContractFileForAgent', [AgentServiceContractController::class, 'storeContractFileForAgent']);
    Route::get('downloadAgentContractFile/{contractId}', [AgentServiceContractController::class, 'downloadContractFile']);
    Route::delete('deleteAgentContractFile/{contractId}', [AgentServiceContractController::class, 'deleteContractFile']);

    // Agent Service Types
    Route::get('getAgentServiceTypes', [AgentServiceTypeController::class, 'index']);

    // Agent Contract Pricing
    Route::get('getAgentContractPricings', [AgentContractPricingController::class, 'index']);
    Route::get('getAgentContractPricing/{id}', [AgentContractPricingController::class, 'show']);
    Route::get('getAgentContractPricingByContract/{contractId}', [AgentContractPricingController::class, 'getByContract']);
    Route::post('storeAgentContractPricing', [AgentContractPricingController::class, 'store']);
    Route::post('updateAgentContractPricing/{id}', [AgentContractPricingController::class, 'update']);
    Route::delete('deleteAgentContractPricing/{id}', [AgentContractPricingController::class, 'destroy']);

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


    // Cities
    Route::get('cities', [\App\Http\Controllers\CityController::class, 'index']);
    Route::post('cities', [\App\Http\Controllers\CityController::class, 'store']);
    Route::get('cities/{id}', [\App\Http\Controllers\CityController::class, 'show']);
    Route::post('cities/{id}', [\App\Http\Controllers\CityController::class, 'update']);

    // Countries
    Route::get('countries', [\App\Http\Controllers\CountryController::class, 'index']);

    // Position Documents (Required Document Names)
    Route::get('positions/{id}/documents', [\App\Http\Controllers\PositionController::class, 'getDocuments']);
    Route::post('positions/{id}/required-documents', [\App\Http\Controllers\PositionController::class, 'addRequiredDocument']);
    Route::delete('positions/{positionId}/required-documents/{documentId}', [\App\Http\Controllers\PositionController::class, 'deleteRequiredDocument']);

    // Position Files (Actual File Uploads)
    Route::post('positions/{id}/files', [\App\Http\Controllers\PositionController::class, 'uploadFile']);
    Route::delete('positions/{positionId}/files/{fileId}', [\App\Http\Controllers\PositionController::class, 'deleteFile']);


    // HR - RECRUTERS
    Route::get('getHRStatistics', [CandidateController::class, 'getHRStatistics']);
    Route::get('getApprovedCandidates', [CandidateController::class, 'getApprovedCandidates']);


    // Statistic for companies
    Route::get('statisticForCompanies', [StatisticController::class, 'statisticForCompanies']);

    // Statistic for agents
    Route::get('statisticForAgents', [StatisticController::class, 'statisticForAgents']);

    // Agents job assignments overview
    Route::get('agents-job-assignments', [StatisticController::class, 'agentsJobAssignments']);

    // Applicants (Candidates without status)
    Route::get('applicants', [CandidateController::class, 'getApplicants']);
});




