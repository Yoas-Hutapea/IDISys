<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Controllers\Master\TermOfPaymentsController;

Route::middleware(['web', 'auth'])
    ->prefix('Finance')
    ->group(function () {
        Route::prefix('Master')->group(function () {
            Route::get('/TermOfPayments', [TermOfPaymentsController::class, 'index']);
        });
    });
