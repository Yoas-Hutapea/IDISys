<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home.index');
})->middleware('auth');

Route::get('/profile', function () {
    return view('account.profile');
})->middleware('auth')->name('profile');

Route::get('/procurement/purchase-request/create', function () {
    return view('procurement.purchase_request.create.index');
})->middleware('auth')->name('procurement.purchase_request.create');

Route::get('/Procurement/PurchaseRequest/Create', function () {
    return view('procurement.purchase_request.create.index');
})->middleware('auth')->name('procurement.purchase_request.create.legacy');
