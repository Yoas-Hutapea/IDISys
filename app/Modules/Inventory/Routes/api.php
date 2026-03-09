<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Controllers\GoodReceiveNotesController;

Route::middleware(['web', 'auth'])
    ->prefix('Inventory')
    ->group(function () {
        Route::post('/GoodReceiveNotes/Save', [GoodReceiveNotesController::class, 'save']);
        Route::post('/GoodReceiveNotes/Headers/Grid', [GoodReceiveNotesController::class, 'headersGrid']);
        Route::post('/GoodReceiveNotes/Headers/ApprovalGrid', [GoodReceiveNotesController::class, 'approvalGrid']);
        Route::put('/GoodReceiveNotes/Headers/{grNumber}/Approval', [GoodReceiveNotesController::class, 'approvalDecision'])
            ->where('grNumber', '.*');
        Route::get('/GoodReceiveNotes/Documents/{grNumber}/documents', [GoodReceiveNotesController::class, 'documents'])
            ->where('grNumber', '.*');
        Route::get('/GoodReceiveNotes/Documents/{documentId}/download', [GoodReceiveNotesController::class, 'downloadDocument']);
        Route::get('/GoodReceiveNotes/Lines/{poNumber}', [GoodReceiveNotesController::class, 'lines'])
            ->where('poNumber', '.*');
        Route::get('/GoodReceiveNotes/ItemsForInvoice/{poNumber}', [GoodReceiveNotesController::class, 'itemsForInvoice'])
            ->where('poNumber', '.*');
        Route::get('/GoodReceiveNotes/RecalcAmortization/{poNumber}', [GoodReceiveNotesController::class, 'recalcAmortization'])
            ->where('poNumber', '.*');
    });
