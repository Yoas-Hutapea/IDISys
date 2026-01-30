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

Route::get('/procurement/purchase-request/list', function () {
    return view('procurement.purchase_request.list.index');
})->middleware('auth')->name('procurement.purchase_request.list');

Route::get('/Procurement/PurchaseRequest/List', function () {
    return view('procurement.purchase_request.list.index');
})->middleware('auth')->name('procurement.purchase_request.list.legacy');

Route::get('/procurement/purchase-request/approval', function () {
    return view('procurement.purchase_request.approval.list.index');
})->middleware('auth')->name('procurement.purchase_request.approval');

Route::get('/Procurement/PurchaseRequest/Approval', function () {
    return view('procurement.purchase_request.approval.list.index');
})->middleware('auth')->name('procurement.purchase_request.approval.legacy');

Route::get('/procurement/purchase-request/receive', function () {
    return view('procurement.purchase_request.receive.list.index');
})->middleware('auth')->name('procurement.purchase_request.receive');

Route::get('/Procurement/PurchaseRequest/Receive', function () {
    return view('procurement.purchase_request.receive.list.index');
})->middleware('auth')->name('procurement.purchase_request.receive.legacy');

Route::get('/procurement/purchase-request/release', function () {
    return view('procurement.purchase_request.release.list.index');
})->middleware('auth')->name('procurement.purchase_request.release');

Route::get('/Procurement/PurchaseRequest/Release', function () {
    return view('procurement.purchase_request.release.list.index');
})->middleware('auth')->name('procurement.purchase_request.release.legacy');

Route::get('/procurement/purchase-order/list', function () {
    return view('procurement.purchase_order.list.index');
})->middleware('auth')->name('procurement.purchase_order.list');

Route::get('/Procurement/PurchaseOrder/List', function () {
    return view('procurement.purchase_order.list.index');
})->middleware('auth')->name('procurement.purchase_order.list.legacy');
