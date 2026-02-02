/**
 * TaskToDoView Module
 * Handles rendering of Task To Do items in dashboard
 */
class TaskToDoView {
    constructor(managerInstance) {
        this.manager = managerInstance;
    }

    /**
     * Render tasks to the DOM
     */
    renderTasks(tasks) {
        const taskListContent = document.getElementById('taskListContent');

        if (!taskListContent) return;

        // If count is 0, show "No Task To Do" message
        if (!tasks || tasks.length === 0) {
            taskListContent.innerHTML = `
                <div class="task-list-empty">
                    <i class="icon-base bx bx-check-circle task-list-empty-icon"></i>
                    <div class="mt-2">No Task To Do</div>
                </div>
            `;
            return;
        }

        // Separate tasks by status:
        // Status 1-3 = Approval (specific users only)
        // Status 4 = Waiting Receive Purchase Request / Finish Purchase Request (Procurement Staff only)
        // Status 5 = Rejected (requestor only)
        // Status 7 = Release Purchase Request (user who received only, based on UpdatedBy)
        // Status 8 or 12 = Confirm Purchase Order (user who created PO only, filtered by CreatedBy matching current user's EmployeeID)
        // Status 9 or 10 = Approval Purchase Order (status 9: Account Payable, Treasury & Revenue Assurance Manager only, status 10: Finance & Treasury Division Head only)
        const approvalTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID !== 4 && statusID !== 5 && statusID !== 7 && statusID !== 8 && statusID !== 12 && statusID !== 9 && statusID !== 10;
        });

        const receiveTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID === 4;
        });

        const rejectedTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID === 5;
        });

        const releaseTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID === 7;
        });

        const confirmPOTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID === 8 || statusID === 12;
        });

        const approvalPOTasks = tasks.filter(task => {
            const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
            const statusID = parseInt(statusIDRaw, 10);
            return statusID === 9 || statusID === 10;
        });

        // Build HTML for task items
        let taskItemsHTML = '';

        // Show "Approval Purchase Request" if there are approval tasks (status 1-3)
        if (approvalTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToApprovalList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Approval Purchase Request</span>
                        <span class="task-count-badge">${approvalTasks.length}</span>
                    </div>
                </div>
            `;
        }

        // Show "Waiting Receive Purchase Request" or "Finish Purchase Request" if there are status 4 tasks
        if (receiveTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToReceivalList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Receival Purchase Request</span>
                        <span class="task-count-badge">${receiveTasks.length}</span>
                    </div>
                </div>
            `;
        }

        // Show "Rejected Purchase Request" if there are rejected tasks
        if (rejectedTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToPRList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Rejected Purchase Request</span>
                        <span class="task-count-badge">${rejectedTasks.length}</span>
                    </div>
                </div>
            `;
        }

        // Show "Release Purchase Request" if there are release tasks (status 7)
        if (releaseTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToReleaseList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Release Purchase Request</span>
                        <span class="task-count-badge">${releaseTasks.length}</span>
                    </div>
                </div>
            `;
        }

        // Show "Confirm Purchase Order" if there are confirm PO tasks (status 8 or 12)
        if (confirmPOTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToConfirmPOList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Confirm Purchase Order</span>
                        <span class="task-count-badge">${confirmPOTasks.length}</span>
                    </div>
                </div>
            `;
        }

        // Show "Approval Purchase Order" if there are approval PO tasks (status 9 or 10)
        if (approvalPOTasks.length > 0) {
            taskItemsHTML += `
                <div class="task-item" style="cursor: pointer;" onclick="goToApprovalPOList()">
                    <div class="d-flex align-items-center justify-content-between" style="padding: 0.75rem 1rem;">
                        <span class="task-name">Approval Purchase Order</span>
                        <span class="task-count-badge">${approvalPOTasks.length}</span>
                    </div>
                </div>
            `;
        }

        taskListContent.innerHTML = taskItemsHTML;
    }

    /**
     * Show error message
     */
    showError(message) {
        const taskListContent = document.getElementById('taskListContent');
        if (taskListContent) {
            taskListContent.innerHTML = `
                <div class="task-list-empty">
                    <i class="icon-base bx bx-error-circle task-list-empty-icon-error"></i>
                    <div class="mt-2 text-danger">${this.escapeHtml(message)}</div>
                </div>
            `;
        }
    }

    /**
     * Show loading state
     */
    showLoading() {
        const taskListContent = document.getElementById('taskListContent');
        if (taskListContent) {
            taskListContent.innerHTML = `
                <div class="task-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Loading tasks...</div>
                </div>
            `;
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.TaskToDoView = TaskToDoView;
}

