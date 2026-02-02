<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Controllers\Master\TermOfPaymentsController;
use App\Modules\Finance\Controllers\EBilling\InvoiceDocumentChecklistsController;
use App\Modules\Finance\Controllers\EBilling\InvoiceWorkTypesController;
use App\Modules\Finance\Controllers\EBilling\InvoiceWorkTypePurchaseTypesController;
use App\Modules\Finance\Controllers\EBilling\InvoiceWorkTypeTOPDocumentChecklistsController;
use App\Modules\Finance\Controllers\Invoice\InvoicesController;

Route::middleware(['web', 'auth'])
    ->prefix('Finance')
    ->group(function () {
        Route::prefix('Master')->group(function () {
            Route::get('/TermOfPayments', [TermOfPaymentsController::class, 'index']);
        });

        Route::prefix('EBilling')->group(function () {
            Route::get('/InvoiceDocumentChecklists', [InvoiceDocumentChecklistsController::class, 'index']);
            Route::get('/InvoiceDocumentChecklists/{id}', [InvoiceDocumentChecklistsController::class, 'show']);
            Route::post('/InvoiceDocumentChecklists', [InvoiceDocumentChecklistsController::class, 'store']);
            Route::put('/InvoiceDocumentChecklists/{id}', [InvoiceDocumentChecklistsController::class, 'update']);

            Route::get('/InvoiceWorkTypes', [InvoiceWorkTypesController::class, 'index']);
            Route::get('/InvoiceWorkTypes/{id}', [InvoiceWorkTypesController::class, 'show']);
            Route::post('/InvoiceWorkTypes', [InvoiceWorkTypesController::class, 'store']);
            Route::put('/InvoiceWorkTypes/{id}', [InvoiceWorkTypesController::class, 'update']);

            Route::get('/InvoiceWorkTypePurchaseTypes', [InvoiceWorkTypePurchaseTypesController::class, 'index']);
            Route::get('/InvoiceWorkTypePurchaseTypes/{id}', [InvoiceWorkTypePurchaseTypesController::class, 'show']);
            Route::post('/InvoiceWorkTypePurchaseTypes', [InvoiceWorkTypePurchaseTypesController::class, 'store']);
            Route::put('/InvoiceWorkTypePurchaseTypes/{id}', [InvoiceWorkTypePurchaseTypesController::class, 'update']);

            Route::get('/InvoiceWorkTypeTOPDocumentChecklists', [InvoiceWorkTypeTOPDocumentChecklistsController::class, 'index']);
            Route::get('/InvoiceWorkTypeTOPDocumentChecklists/{id}', [InvoiceWorkTypeTOPDocumentChecklistsController::class, 'show']);
            Route::post('/InvoiceWorkTypeTOPDocumentChecklists', [InvoiceWorkTypeTOPDocumentChecklistsController::class, 'store']);
            Route::put('/InvoiceWorkTypeTOPDocumentChecklists/{id}', [InvoiceWorkTypeTOPDocumentChecklistsController::class, 'update']);
        });

        Route::prefix('Invoice')->group(function () {
            Route::get('/Invoices/Details', [InvoicesController::class, 'detailsByPurchOrderID']);
            Route::post('/Invoices/BulkPost', [InvoicesController::class, 'bulkPost']);
            Route::get('/Invoices/{id}/Items', [InvoicesController::class, 'items']);
            Route::get('/Invoices/{id}/Documents', [InvoicesController::class, 'documents']);
            Route::post('/Invoices/{id}/Post', [InvoicesController::class, 'postInvoice']);
            Route::post('/Invoices/{id}/Reject', [InvoicesController::class, 'rejectInvoice']);
            Route::get('/Invoices/{id}', [InvoicesController::class, 'show']);
            Route::put('/Invoices/{id}', [InvoicesController::class, 'update']);
            Route::get('/Invoices', [InvoicesController::class, 'index']);
            Route::post('/Invoices', [InvoicesController::class, 'store']);
        });
    });
