<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Procurement\Controllers\PurchaseOrderController;
use App\Modules\Procurement\Controllers\PurchaseRequestController;
use App\Modules\Procurement\Controllers\Master\ApplicantsController;
use App\Modules\Procurement\Controllers\Master\ApprovalStatusesController;
use App\Modules\Procurement\Controllers\Master\BillingTypesController;
use App\Modules\Procurement\Controllers\Master\CompaniesController;
use App\Modules\Procurement\Controllers\Master\CurrenciesController;
use App\Modules\Procurement\Controllers\Master\CoreBusinessesController;
use App\Modules\Procurement\Controllers\Master\EmployeesController;
use App\Modules\Procurement\Controllers\Master\InventoriesController;
use App\Modules\Procurement\Controllers\Master\PurchaseTypesController;
use App\Modules\Procurement\Controllers\Master\RegionsController;
use App\Modules\Procurement\Controllers\Master\STIPSitesController;
use App\Modules\Procurement\Controllers\Master\SubCoreBusinessesController;
use App\Modules\Procurement\Controllers\Master\UnitsController;
use App\Modules\Procurement\Controllers\Master\VendorContractsController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestsController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestApprovalsController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestAdditionalController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestReceivesController;
use App\Modules\Procurement\Controllers\PurchaseRequest\PurchaseRequestReleasesController;
use App\Modules\Procurement\Controllers\Master\VendorsController;

Route::middleware(['web', 'auth'])
    ->prefix('Procurement')
    ->group(function () {
        Route::prefix('Master')->group(function () {
            Route::get('/Applicants', [ApplicantsController::class, 'index']);
            Route::get('/Employees', [EmployeesController::class, 'index']);
            Route::get('/Companies', [CompaniesController::class, 'index']);
            Route::get('/ApprovalStatuses', [ApprovalStatusesController::class, 'index']);
            Route::get('/Regions', [RegionsController::class, 'index']);
            Route::get('/CoreBusinesses', [CoreBusinessesController::class, 'index']);
            Route::get('/SubCoreBusinesses', [SubCoreBusinessesController::class, 'index']);
            Route::get('/Vendors', [VendorsController::class, 'index']);
            Route::get('/Vendors/{id}', [VendorsController::class, 'show']);
            Route::get('/VendorContracts', [VendorContractsController::class, 'index']);
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
        Route::post('/PurchaseRequest/PurchaseRequestReceives/Grid', [PurchaseRequestReceivesController::class, 'grid']);
        Route::post('/PurchaseRequest/PurchaseRequestReceives/Bulk', [PurchaseRequestReceivesController::class, 'bulk']);
        Route::post('/PurchaseRequest/PurchaseRequestReceives/{prNumber}', [PurchaseRequestReceivesController::class, 'store'])
            ->where('prNumber', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestReleases', [PurchaseRequestController::class, 'releases']);
        Route::post('/PurchaseRequest/PurchaseRequestReleases/Grid', [PurchaseRequestReleasesController::class, 'grid']);
        Route::post('/PurchaseRequest/PurchaseRequestReleases/Bulk', [PurchaseRequestReleasesController::class, 'bulk']);
        Route::post('/PurchaseRequest/PurchaseRequestReleases/{prNumber}', [PurchaseRequestReleasesController::class, 'store'])
            ->where('prNumber', '.*');
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
        Route::get('/PurchaseRequest/PurchaseRequestDocuments/{documentId}/download', [PurchaseRequestsController::class, 'downloadDocument'])
            ->where('documentId', '.*');
        Route::get('/PurchaseRequest/PurchaseRequestAdditional/{prNumber}', [PurchaseRequestsController::class, 'additional'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestAdditional/batch-check', [PurchaseRequestAdditionalController::class, 'batchCheck']);
        Route::post('/PurchaseRequest/PurchaseRequestItems/{prNumber}/items/bulk', [PurchaseRequestsController::class, 'saveItemsBulk'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestDocuments/{prNumber}/documents/bulk', [PurchaseRequestsController::class, 'saveDocumentsBulk'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestDocuments/{prNumber}/documents/upload', [PurchaseRequestsController::class, 'uploadDocument'])
            ->where('prNumber', '.*');
        Route::post('/PurchaseRequest/PurchaseRequestAdditional', [PurchaseRequestsController::class, 'saveAdditional']);

        Route::post('/PurchaseOrder/PurchaseOrders/Grid', [PurchaseOrderController::class, 'grid']);
        Route::post('/PurchaseOrder/PurchaseOrderItems/Grid', [PurchaseOrderController::class, 'purchaseOrderItemsGrid']);
        Route::get('/PurchaseOrder/CancelPeriod/{poNumber}/Amortizations', [PurchaseOrderController::class, 'cancelPeriodAmortizations'])
            ->where('poNumber', '.*');
        Route::post('/PurchaseOrder/CancelPeriod/Cancel', [PurchaseOrderController::class, 'cancelPeriodSubmit']);
        Route::get('/PurchaseOrder/PurchaseOrders/{poNumber}/items', [PurchaseOrderController::class, 'items'])
            ->where('poNumber', '.*');
        Route::get('/PurchaseOrder/PurchaseOrders/{poNumber}', [PurchaseOrderController::class, 'show'])
            ->where('poNumber', '.*');
        Route::post('/PurchaseOrder/Approval/Bulk', [PurchaseOrderController::class, 'approvalBulk']);
        Route::get('/PurchaseOrder/Approval/History/{poNumber}', [PurchaseOrderController::class, 'approvalHistory'])
            ->where('poNumber', '.*');
        Route::post('/PurchaseOrder/Approval/{poNumber}', [PurchaseOrderController::class, 'approvalSubmit'])
            ->where('poNumber', '.*');
        Route::post('/PurchaseOrder/Confirm/Submit', [PurchaseOrderController::class, 'confirmSubmit']);
        Route::post('/PurchaseOrder/Confirm/Bulk', [PurchaseOrderController::class, 'confirmBulk']);
        Route::post('/PurchaseOrder/UpdateBulkyPrice/Submit', [PurchaseOrderController::class, 'updateBulkyPriceSubmit']);
    });
