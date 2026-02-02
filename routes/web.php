<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Modules\Procurement\Controllers\PurchaseOrderController;

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

Route::get('/procurement/purchase-order/confirm', function () {
    return view('procurement.purchase_order.confirm.list.index');
})->middleware('auth')->name('procurement.purchase_order.confirm');

Route::get('/Procurement/PurchaseOrder/Confirm', function () {
    return view('procurement.purchase_order.confirm.list.index');
})->middleware('auth')->name('procurement.purchase_order.confirm.legacy');

Route::get('/procurement/purchase-order/approval', function () {
    return view('procurement.purchase_order.approval.list.index');
})->middleware('auth')->name('procurement.purchase_order.approval');

Route::get('/Procurement/PurchaseOrder/Approval', function () {
    return view('procurement.purchase_order.approval.list.index');
})->middleware('auth')->name('procurement.purchase_order.approval.legacy');

Route::get('/procurement/purchase-order/cancel-period', function () {
    return view('procurement.purchase_order.cancel_period.index');
})->middleware('auth')->name('procurement.purchase_order.cancel_period');

Route::get('/Procurement/PurchaseOrder/CancelPeriod', function () {
    return view('procurement.purchase_order.cancel_period.index');
})->middleware('auth')->name('procurement.purchase_order.cancel_period.legacy');

Route::get('/Procurement/PurchaseOrder/Document/DownloadDocument', [PurchaseOrderController::class, 'downloadDocument'])
    ->middleware('auth')
    ->name('procurement.purchase_order.document.download');

Route::get('/Finance/EBilling/MasterDocumentChecklist', function () {
    return view('finance.ebilling.master_document_checklist.index');
})->middleware('auth')->name('finance.ebilling.master_document_checklist');

Route::get('/Finance/EBilling/MasterWorkType', function () {
    return view('finance.ebilling.master_work_type.index');
})->middleware('auth')->name('finance.ebilling.master_work_type');

Route::get('/Finance/EBilling/MasterWorkTypePurchaseType', function () {
    return view('finance.ebilling.master_work_type_purchase_type.index');
})->middleware('auth')->name('finance.ebilling.master_work_type_purchase_type');

Route::get('/Finance/EBilling/MasterWorkTypeTOPDocumentChecklist', function () {
    return view('finance.ebilling.master_work_type_top_document_checklist.index');
})->middleware('auth')->name('finance.ebilling.master_work_type_top_document_checklist');

Route::get('/Finance/EBilling/Invoice/List', function () {
    return view('finance.invoice.list.index');
})->middleware('auth')->name('finance.ebilling.invoice.list');

Route::get('/Finance/EBilling/Invoice/Create', function () {
    $employee = session('employee');
    $currentUserName = $employee?->name ?? (optional(Auth::user())->name ?? optional(Auth::user())->Username ?? '');
    $currentUserEmail = $employee?->Email ?? optional(Auth::user())->email ?? '';

    return view('finance.invoice.create.index', [
        'currentUserName' => $currentUserName,
        'currentUserEmail' => $currentUserEmail,
    ]);
})->middleware('auth')->name('finance.ebilling.invoice.create');

Route::get('/Finance/EBilling/Invoice/Posting', function () {
    return view('finance.invoice.posting.index');
})->middleware('auth')->name('finance.ebilling.invoice.posting');

Route::get('/Finance/VendorManagement', function () {
    return view('finance.vendor_management.index');
})->middleware('auth')->name('finance.vendor_management.index');

Route::get('/Finance/VendorManagement/VendorData', function () {
    return view('finance.vendor_management.vendor_data.index');
})->middleware('auth')->name('finance.vendor_management.vendor_data');

Route::get('/Finance/VendorManagement/VendorContract', function () {
    return view('finance.vendor_management.vendor_contract.index');
})->middleware('auth')->name('finance.vendor_management.vendor_contract');
