@extends('layouts.app')

@section('title', 'Confirm Purchase Order')

@section('styles')
    <style>
        .confirm-po-list-container {
            contain: layout style;
        }

        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            contain: layout;
            transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] .filter-section {
            background-color: #2b2b2b;
            border-color: #495057;
        }

        .filter-header {
            background-color: #2352a1;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem 0.375rem 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .filter-header:hover {
            background-color: #0b5ed7;
        }

        .filter-content {
            padding: 1.5rem;
            contain: layout;
            display: none;
        }

        .filter-content.show {
            display: block;
        }

        .list-section {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            contain: layout;
        }

        [data-bs-theme="dark"] .list-section {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }

        .list-partial-content {
            padding: 1.5rem;
        }

        .list-partial-content .dataTables_wrapper {
            padding: 0;
        }

        .list-partial-content .dataTables_wrapper .dt-buttons {
            margin-bottom: 1rem;
        }

        .list-partial-content .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .list-partial-content .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .list-partial-content .dataTables_wrapper .dataTables_info {
            margin-top: 1rem;
        }

        .list-partial-content .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }

        .list-header {
            background-color: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 0.375rem 0.375rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            contain: layout;
        }

        [data-bs-theme="dark"] .list-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 0.25rem;
            contain: layout;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border-radius: 0.25rem;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        .table-responsive {
            border-radius: 0 0 0.375rem 0.375rem;
            contain: layout;
            overflow: auto;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .rows-per-page {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y confirm-po-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Procurement /</span> Confirm Purchase Order
                    </h5>
                </div>
            </div>
        </div>

        <section class="filter-section mb-4" aria-labelledby="filter-heading">
            <header class="filter-header" onclick="toggleFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="filter-content">
                <span id="filter-heading">
                    <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                    Filter
                </span>
                <i class="icon-base bx bx-chevron-down" id="filter-chevron" aria-hidden="true"></i>
            </header>
            <div class="filter-content" id="filter-content" role="region" aria-labelledby="filter-heading">
                @include('procurement.purchase_order.confirm.list.partials._FilterPartial')
            </div>
        </section>

        <section class="list-section" id="listSection" aria-labelledby="list-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="list-heading" class="fw-semibold mb-0">
                        List Purchase Order<span id="dateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em; font-weight: normal;"></span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-success" id="exportExcelBtn" onclick="exportExcel('#confirmPOTable')" title="Export to Excel">
                        <i class="icon-base bx bx-file me-1"></i>
                        Excel
                    </button>
                    <button class="btn btn-warning" id="processBulkConfirmBtn" onclick="confirmPOManager.processBulkConfirm()" disabled>
                        <i class="icon-base bx bx-check-square me-1"></i>
                        Process
                    </button>
                    <button class="btn btn-primary" id="updateBulkyPriceBtn" onclick="confirmPOManager.showUpdateBulkyPrice()">
                        Update Bulky Price
                    </button>
                </div>
            </header>

            @include('procurement.purchase_order.confirm.list.partials._ListPartial')
        </section>

        <section class="list-section" id="viewConfirmSection" style="display: none;" aria-labelledby="view-confirm-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="view-confirm-heading" class="fw-semibold mb-0">Confirm Purchase Order</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="confirmPOManager.backToList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back to List
                    </button>
                </div>
            </header>

            <div id="viewConfirmContainer">
                @include('procurement.purchase_order.confirm.view._ViewConfirm')
            </div>
        </section>

        <section class="list-section" id="updateBulkyPriceSection" style="display: none;" aria-labelledby="update-bulky-price-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="update-bulky-price-heading" class="fw-semibold mb-0">Update Bulky Price</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="confirmPOManager.backToConfirmList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back
                    </button>
                </div>
            </header>

            <div id="updateBulkyPriceContainer">
                @include('procurement.purchase_order.confirm.update_bulky_price._UpdateBulkyPricePartial')
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            'use strict';

            if (!window.sharedConfirmPOData) {
                window.sharedConfirmPOData = {
                    purchaseTypes: null,
                    purchaseSubTypes: null,
                    companies: null,
                    vendors: null
                };
            }

            if (!window.sharedConfirmPOLoaders) {
                window.sharedConfirmPOLoaders = {
                    purchaseTypesPromise: null,
                    purchaseSubTypesPromise: null,
                    companiesPromise: null,
                    vendorsPromise: null,
                    purchaseTypesLoading: false,
                    purchaseSubTypesLoading: false,
                    companiesLoading: false,
                    vendorsLoading: false
                };
            }

            window.loadSharedPurchaseTypes = async function() {
                const loaders = window.sharedConfirmPOLoaders;
                const data = window.sharedConfirmPOData;

                if (data.purchaseTypes && data.purchaseTypes.length > 0) {
                    return Promise.resolve(data.purchaseTypes);
                }

                if (loaders.purchaseTypesLoading && loaders.purchaseTypesPromise) {
                    return loaders.purchaseTypesPromise;
                }

                loaders.purchaseTypesLoading = true;
                loaders.purchaseTypesPromise = (async () => {
                    try {
                        if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseTypes === 'function') {
                            const types = await window.procurementSharedCache.getPurchaseTypes();
                            data.purchaseTypes = Array.isArray(types) ? types : [];
                            return data.purchaseTypes;
                        }

                        const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
                        const responseData = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                            .catch((error) => {
                                if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                    return null;
                                }
                                throw error;
                            });
                        const types = responseData?.data ?? responseData ?? [];

                        if (!Array.isArray(types) || types.length === 0) {
                            data.purchaseTypes = [];
                            return [];
                        }

                        data.purchaseTypes = types;
                        return types;
                    } catch (error) {
                        throw error;
                    } finally {
                        loaders.purchaseTypesLoading = false;
                        loaders.purchaseTypesPromise = null;
                    }
                })();

                return loaders.purchaseTypesPromise;
            };

            window.loadSharedPurchaseSubTypes = async function() {
                const loaders = window.sharedConfirmPOLoaders;
                const data = window.sharedConfirmPOData;

                if (data.purchaseSubTypes && data.purchaseSubTypes.length > 0) {
                    return Promise.resolve(data.purchaseSubTypes);
                }

                if (loaders.purchaseSubTypesLoading && loaders.purchaseSubTypesPromise) {
                    return loaders.purchaseSubTypesPromise;
                }

                loaders.purchaseSubTypesLoading = true;
                loaders.purchaseSubTypesPromise = (async () => {
                    try {
                        const endpoint = '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true';
                        const responseData = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                            .catch((error) => {
                                if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                    return null;
                                }
                                throw error;
                            });
                        const payload = responseData?.data ?? responseData ?? [];
                        const subTypes = Array.isArray(payload) ? payload : [];

                        if (!Array.isArray(subTypes) || subTypes.length === 0) {
                            data.purchaseSubTypes = [];
                            return [];
                        }

                        data.purchaseSubTypes = subTypes;
                        return subTypes;
                    } catch (error) {
                        throw error;
                    } finally {
                        loaders.purchaseSubTypesLoading = false;
                        loaders.purchaseSubTypesPromise = null;
                    }
                })();

                return loaders.purchaseSubTypesPromise;
            };

            window.loadSharedCompanies = async function() {
                const loaders = window.sharedConfirmPOLoaders;
                const data = window.sharedConfirmPOData;

                if (data.companies && data.companies.length > 0) {
                    return Promise.resolve(data.companies);
                }

                if (loaders.companiesLoading && loaders.companiesPromise) {
                    return loaders.companiesPromise;
                }

                loaders.companiesLoading = true;
                loaders.companiesPromise = (async () => {
                    try {
                        const endpoint = '/Procurement/Master/Companies?isActive=true';
                        const responseData = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                            .catch((error) => {
                                if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                    return null;
                                }
                                throw error;
                            });
                        const companies = responseData?.data ?? responseData ?? [];

                        if (!Array.isArray(companies) || companies.length === 0) {
                            data.companies = [];
                            return [];
                        }

                        data.companies = companies;
                        return companies;
                    } catch (error) {
                        throw error;
                    } finally {
                        loaders.companiesLoading = false;
                        loaders.companiesPromise = null;
                    }
                })();

                return loaders.companiesPromise;
            };

            window.loadSharedVendors = async function() {
                const loaders = window.sharedConfirmPOLoaders;
                const data = window.sharedConfirmPOData;

                if (data.vendors && data.vendors.length > 0) {
                    return Promise.resolve(data.vendors);
                }

                if (loaders.vendorsLoading && loaders.vendorsPromise) {
                    return loaders.vendorsPromise;
                }

                loaders.vendorsLoading = true;
                loaders.vendorsPromise = (async () => {
                    try {
                        const endpoint = '/Procurement/Master/Vendors?isActive=true';
                        const responseData = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                            .catch((error) => {
                                if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                    return null;
                                }
                                throw error;
                            });
                        const vendors = responseData?.data ?? responseData ?? [];

                        if (!Array.isArray(vendors) || vendors.length === 0) {
                            data.vendors = [];
                            return [];
                        }

                        data.vendors = vendors;
                        return vendors;
                    } catch (error) {
                        throw error;
                    } finally {
                        loaders.vendorsLoading = false;
                        loaders.vendorsPromise = null;
                    }
                })();

                return loaders.vendorsPromise;
            };
        })();
    </script>

    @include('shared.scripts.components._ConfirmPOListScriptsPartial')
    @include('shared.scripts.components._UpdateBulkyPriceScriptsPartial')
@endsection
