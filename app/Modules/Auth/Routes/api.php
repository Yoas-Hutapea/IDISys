<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\LoginController;

Route::middleware('web')
    ->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('api.auth.login.submit');
        Route::post('/logout', [LoginController::class, 'logout'])->name('api.auth.logout');
    });
