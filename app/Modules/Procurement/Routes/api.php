<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Procurement\Controllers\PurchaseOrderController;
use App\Modules\Procurement\Controllers\PurchaseRequestController;
use App\Modules\Procurement\Controllers\Master\ApplicantsController;
use App\Modules\Procurement\Controllers\Master\ApprovalStatusesController;
use App\Modules\Procurement\Controllers\Master\BillingTypesController;
use App\Modules\Procurement\Controllers\Master\CompaniesController;
use App\Modules\Procurement\Controllers\Master\CurrenciesController;
use App\Modules\Procurement\Controllers\Master\EmployeesController;
use App\Modules\Procurement\Controllers\Master\InventoriesController;
use App\Modules\Procurement\Controllers\Master\PurchaseTypesController;
use App\Modules\Procurement\Controllers\Master\RegionsController;
use App\Modules\Procurement\Controllers\Master\STIPSitesController;
use App\Modules\Procurement\Controllers\Master\UnitsController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestsController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestApprovalsController;

Route::middleware(['web', 'auth'])
    ->prefix('Procurement')
    ->group(function () {
        Route::prefix('Master')->group(function () {
            Route::get('/Applicants', [ApplicantsController::class, 'index']);
            Route::get('/Employees', [EmployeesController::class, 'index']);
            Route::get('/Companies', [CompaniesController::class, 'index']);
            Route::get('/ApprovalStatuses', [ApprovalStatusesController::class, 'index']);
            Route::get('/Regions', [RegionsController::class, 'index']);
            Route::get('/PurchaseTypes/List', [PurchaseTypesController::class, 'list']);
            Route::get('/PurchaseTypes/SubTypes', [PurchaseTypesController::class, 'subTypes']);
            Route::get('/Units', [UnitsController::class, 'index']);
            Route::get('/Currencies', [CurrenciesController::class, 'index']);
            Route::get('/BillingTypes', [BillingTypesController::class, 'index']);
            Route::get('/Inventories', [InventoriesController::class, 'index']);
            Route::get('/STIPSites', [STIPSitesController::class, 'index']);
        });

        Route::get('/PurchaseRequest/PurchaseRequestApprovals', [PurchaseRequestController::class, 'approvals']);
        Route::post('/PurchaseRequest/PurchaseRequestApprovals/Grid', [PurchaseRequestsController::class, 'grid']);
        Route::post('/PurchaseRequest/PurchaseRequestApprovals/{prNumber}', [PurchaseRequestApprovalsController::class, 'store'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestApprovals/History/{prNumber}', [PurchaseRequestApprovalsController::class, 'history'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestReceives', [PurchaseRequestController::class, 'receives']);
        Route::get('/PurchaseRequest/PurchaseRequestReleases', [PurchaseRequestController::class, 'releases']);
        Route::get('/PurchaseRequest/PurchaseRequests', [PurchaseRequestController::class, 'index']);

        Route::post('/PurchaseRequest/PurchaseRequests/Grid', [PurchaseRequestsController::class, 'grid']);
        Route::post('/PurchaseRequest/PurchaseRequests', [PurchaseRequestsController::class, 'store']);
        Route::get('/PurchaseRequest/PurchaseRequests', [PurchaseRequestsController::class, 'index']);
        Route::put('/PurchaseRequest/PurchaseRequests/{prNumber}/approval', [PurchaseRequestsController::class, 'updateApproval'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequests/{prNumber}/approval', [PurchaseRequestsController::class, 'updateApproval'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequests/{prNumber}', [PurchaseRequestsController::class, 'show'])
            ->where('prNumber', '.*');
        Route::put('/PurchaseRequest/PurchaseRequests/{prNumber}', [PurchaseRequestsController::class, 'update'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestItems/{prNumber}/items', [PurchaseRequestsController::class, 'items'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestDocuments/{prNumber}/documents', [PurchaseRequestsController::class, 'documents'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestAdditional/{prNumber}', [PurchaseRequestsController::class, 'additional'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestItems/{prNumber}/items/bulk', [PurchaseRequestsController::class, 'saveItemsBulk'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestDocuments/{prNumber}/documents/bulk', [PurchaseRequestsController::class, 'saveDocumentsBulk'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestDocuments/{prNumber}/documents/upload', [PurchaseRequestsController::class, 'uploadDocument'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestAdditional', [PurchaseRequestsController::class, 'saveAdditional']);

        Route::post('/PurchaseOrder/PurchaseOrders/Grid', [PurchaseOrderController::class, 'grid']);
    });
