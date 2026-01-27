@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    <style>
        .task-to-do-card {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            overflow: hidden;
        }

        .task-to-do-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .task-to-do-header-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .task-to-do-header-left i {
            font-size: 1.25rem;
            color: #000;
        }

        .task-to-do-title {
            font-weight: bold;
            color: #000;
            margin: 0;
        }

        .task-count-badge {
            background-color: #6c757d;
            color: white;
            border-radius: 0.5rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 2rem;
            text-align: center;
            display: inline-block;
            margin-left: 0.5rem;
            white-space: nowrap;
        }

        .task-to-do-chevron {
            background-color: #f8f9fa;
            border: none;
            border-radius: 0.25rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .task-to-do-chevron:hover {
            background-color: #e9ecef;
        }

        .task-to-do-content {
            padding: 0;
        }

        .task-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: #e3f2fd;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid rgba(25, 118, 210, 0.1);
        }

        .task-item:hover {
            background-color: #bbdefb;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-number-badge {
            background-color: #009688;
            color: white;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 2rem;
            text-align: center;
            margin-right: 0.75rem;
        }

        .task-name {
            color: #1976d2;
            font-weight: 500;
            flex: 1;
        }

        .task-list-empty {
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }

        .task-list-empty-icon {
            font-size: 2rem;
            color: #6c757d;
        }

        .task-list-empty-icon-error {
            font-size: 2rem;
            color: #dc3545;
        }

        .task-loading {
            padding: 2rem;
            text-align: center;
        }

        [data-bs-theme="dark"] .task-to-do-card {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }

        [data-bs-theme="dark"] .task-to-do-header {
            border-bottom-color: #495057;
        }

        [data-bs-theme="dark"] .task-to-do-header-left i {
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .task-to-do-title {
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .task-to-do-chevron {
            background-color: #3a3a3a;
        }

        [data-bs-theme="dark"] .task-to-do-chevron:hover {
            background-color: #495057;
        }

        [data-bs-theme="dark"] .task-to-do-chevron i {
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .task-item {
            background-color: #1e3a5f;
            border-bottom-color: rgba(25, 118, 210, 0.2);
        }

        [data-bs-theme="dark"] .task-item:hover {
            background-color: #2a4a7f;
        }

        [data-bs-theme="dark"] .task-name {
            color: #64b5f6;
        }

        [data-bs-theme="dark"] .task-list-empty {
            color: #adb5bd;
        }

        [data-bs-theme="dark"] .task-loading {
            color: #adb5bd;
        }

        [data-bs-theme="dark"] .task-list-empty-icon {
            color: #adb5bd;
        }

        [data-bs-theme="dark"] .task-list-empty-icon-error {
            color: #f56565;
        }
    </style>
@endsection

@section('content')
<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="task-to-do-card">
                    <div class="task-to-do-header">
                        <div class="task-to-do-header-left">
                            <i class="icon-base bx bx-calendar"></i>
                            <span class="task-to-do-title">Task To Do</span>
                        </div>
                        <button class="task-to-do-chevron" onclick="goToApprovalList()" title="View All Tasks">
                            <i class="icon-base bx bx-chevron-left"></i>
                        </button>
                    </div>
                    <div class="task-to-do-content" id="taskListContent">
                        <div class="task-loading">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2 text-muted">Loading tasks...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->
</div>
<!-- Content wrapper -->
@endsection

@section('scripts')
    <script src="{{ asset('js/dashboard/TaskToDoAPI.js') }}"></script>
    <script src="{{ asset('js/dashboard/TaskToDoView.js') }}"></script>

    <script>
        'use strict';

        const currentUserEmployeeID = '{{ auth()->user()->Employ_Id ?? '' }}';

        class TaskToDoManager {
            constructor() {
                this.tasks = [];
                this.maxDisplayTasks = 5;

                if (typeof TaskToDoAPI !== 'undefined') {
                    this.apiModule = new TaskToDoAPI();
                }
                if (typeof TaskToDoView !== 'undefined') {
                    this.viewModule = new TaskToDoView(this);
                }

                this.init();
            }

            init() {
                this.loadTasks();
            }

            async loadTasks() {
                try {
                    if (this.viewModule && this.viewModule.showLoading) {
                        this.viewModule.showLoading();
                    }

                    if (this.apiModule && this.apiModule.getAllTasks) {
                        this.tasks = await this.apiModule.getAllTasks(currentUserEmployeeID);
                    } else {
                        console.warn('TaskToDoAPI module not available');
                        this.tasks = [];
                    }

                    if (this.viewModule && this.viewModule.renderTasks) {
                        this.viewModule.renderTasks(this.tasks);
                    } else {
                        console.warn('TaskToDoView module not available');
                        this.renderTasks();
                    }
                } catch (error) {
                    console.error('Error loading tasks:', error);
                    if (this.viewModule && this.viewModule.showError) {
                        this.viewModule.showError('Failed to load tasks');
                    } else {
                        this.showError('Failed to load tasks');
                    }
                }
            }

            renderTasks() {
                if (this.viewModule && this.viewModule.renderTasks) {
                    return this.viewModule.renderTasks(this.tasks);
                }
                console.warn('TaskToDoView module not available');
            }

            escapeHtml(text) {
                if (this.viewModule && this.viewModule.escapeHtml) {
                    return this.viewModule.escapeHtml(text);
                }
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showError(message) {
                if (this.viewModule && this.viewModule.showError) {
                    return this.viewModule.showError(message);
                }
                console.warn('TaskToDoView module not available');
            }
        }

        function goToApprovalList() {
            window.location.href = '/Procurement/PurchaseRequest/Approval';
        }

        function goToReceivalList() {
            window.location.href = '/Procurement/PurchaseRequest/Receive';
        }

        function goToPRList() {
            window.location.href = '/Procurement/PurchaseRequest/List';
        }

        function goToReleaseList() {
            window.location.href = '/Procurement/PurchaseRequest/Release';
        }

        function goToConfirmPOList() {
            window.location.href = '/Procurement/PurchaseOrder/Confirm';
        }

        function goToApprovalPOList() {
            window.location.href = '/Procurement/PurchaseOrder/Approval';
        }

        let taskToDoManager;
        document.addEventListener('DOMContentLoaded', function () {
            taskToDoManager = new TaskToDoManager();
        });
    </script>
@endsection
