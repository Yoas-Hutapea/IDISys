<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Controllers\GoodReceiveNotesController;

Route::middleware(['web', 'auth'])
    ->prefix('Inventory')
    ->group(function () {
        Route::post('/GoodReceiveNotes/Save', [GoodReceiveNotesController::class, 'save']);
        Route::get('/GoodReceiveNotes/Lines/{poNumber}', [GoodReceiveNotesController::class, 'lines'])
            ->where('poNumber', '.*');
        Route::get('/GoodReceiveNotes/ItemsForInvoice/{poNumber}', [GoodReceiveNotesController::class, 'itemsForInvoice'])
            ->where('poNumber', '.*');
        Route::get('/GoodReceiveNotes/RecalcAmortization/{poNumber}', [GoodReceiveNotesController::class, 'recalcAmortization'])
            ->where('poNumber', '.*');
    });
