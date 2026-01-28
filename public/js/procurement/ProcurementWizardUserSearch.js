/**
 * Procurement Wizard User Search Module
 * Handles User/Employee search modal: search, pagination, and selection
 */

class ProcurementWizardUserSearch {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Open user search modal
     */
    async openUserSearchModal(searchTerm = null, targetFieldId = null) {
        const modalId = 'userSearchModal';
        const existingModal = document.getElementById(modalId);
        
        // Remove existing modal and its event listeners before creating new one
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Generate unique IDs for modal elements to prevent ID conflicts
        const uniqueId = Date.now();
        const searchInputId = `employeeSearchInput_${uniqueId}`;
        const searchBtnId = `searchEmployeeBtn_${uniqueId}`;
        const tableBodyId = `employeeTableBody_${uniqueId}`;
        const paginationId = `employeePagination_${uniqueId}`;
        const pageInfoId = `employeePageInfo_${uniqueId}`;
        
        // Create modal HTML with unique IDs
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" data-target-field="${targetFieldId || ''}">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Employee</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="${searchInputId}" placeholder="Search by name, employee ID, position, or department..." value="${searchTerm || ''}">
                                    <button class="btn btn-outline-primary" type="button" id="${searchBtnId}" data-unique-id="${uniqueId}">
                                        <i class="icon-base bx bx-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr><th>Name</th><th>Position</th><th>Department</th><th>Action</th></tr>
                                    </thead>
                                    <tbody id="${tableBodyId}">
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                Loading employees...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div id="${pageInfoId}" class="text-muted small">
                                    Showing 0 - 0 of 0 employees
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="${paginationId}">
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById(modalId);
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Store pagination state in modal data attribute
        modalElement.setAttribute('data-current-page', '1');
        modalElement.setAttribute('data-page-size', '10');
        modalElement.setAttribute('data-total-employees', '0');
        modalElement.setAttribute('data-all-employees', '[]');
        
        // Load employees
        await this.loadEmployeesToModal(searchTerm, targetFieldId, tableBodyId, paginationId, pageInfoId, uniqueId);

        // Add search functionality with unique IDs
        const performSearch = async (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const searchInput = document.getElementById(searchInputId);
            const searchValue = searchInput?.value || null;
            modalElement.setAttribute('data-current-page', '1');
            await this.loadEmployeesToModal(searchValue, targetFieldId, tableBodyId, paginationId, pageInfoId, uniqueId);
        };

        // Use event delegation on modal to handle search button click
        modalElement.addEventListener('click', (e) => {
            if (!modalElement.contains(e.target)) return;
            
            const clickedBtn = e.target.closest(`#${searchBtnId}`);
            if (clickedBtn && clickedBtn.id === searchBtnId) {
                performSearch(e);
            }
        });

        // Handle Enter key in search input
        const searchInput = document.getElementById(searchInputId);
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    performSearch(e);
                }
            });
        }
        
        // Clean up event listeners when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function cleanup() {
            modalElement.removeEventListener('hidden.bs.modal', cleanup);
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.dispose();
            }
            if (modalElement && modalElement.parentNode) {
                modalElement.remove();
            }
        }, { once: true });
    }

    /**
     * Load employees to modal
     */
    async loadEmployeesToModal(searchTerm = null, targetFieldId = null, tableBodyId = 'employeeTableBody', paginationId = null, pageInfoId = null, uniqueId = null) {
        const tableBody = document.getElementById(tableBodyId);
        if (!tableBody) return;

        const modalElement = document.getElementById('userSearchModal');
        if (!modalElement) return;

        // Get pagination state
        let currentPage = parseInt(modalElement.getAttribute('data-current-page')) || 1;
        const pageSize = parseInt(modalElement.getAttribute('data-page-size')) || 10;
        let allEmployees = [];

        // Check if we need to fetch new data
        const cachedEmployees = modalElement.getAttribute('data-all-employees');
        const cachedSearchTerm = modalElement.getAttribute('data-cached-search-term');
        
        const normalizedSearchTerm = (searchTerm || '').trim();
        const normalizedCachedSearchTerm = (cachedSearchTerm || '').trim();
        
        // Show loading state
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading employees...
                </td>
            </tr>
        `;

        try {
            // Fetch from API only if search term changed or no cached data
            if (normalizedCachedSearchTerm !== normalizedSearchTerm || !cachedEmployees || cachedEmployees === '[]') {
                let endpoint = '/Procurement/Master/Employees';
                const queryParams = [];
                if (searchTerm && searchTerm.trim()) {
                    queryParams.push(`searchTerm=${encodeURIComponent(searchTerm.trim())}`);
                }
                if (queryParams.length > 0) {
                    endpoint += '?' + queryParams.join('&');
                }

                const data = await apiCall('Procurement', endpoint, 'GET');
                allEmployees = data.data || data;

                if (!Array.isArray(allEmployees)) {
                    allEmployees = [];
                }

                // Cache the results
                modalElement.setAttribute('data-all-employees', JSON.stringify(allEmployees));
                modalElement.setAttribute('data-cached-search-term', normalizedSearchTerm);
                modalElement.setAttribute('data-total-employees', allEmployees.length.toString());
                
                currentPage = 1;
                modalElement.setAttribute('data-current-page', '1');
            } else {
                // Use cached data
                allEmployees = JSON.parse(cachedEmployees);
            }

            const totalEmployees = allEmployees.length;

            if (totalEmployees === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">No employees found</td>
                    </tr>
                `;
                if (paginationId) {
                    const pagination = document.getElementById(paginationId);
                    if (pagination) pagination.innerHTML = '';
                }
                if (pageInfoId) {
                    const pageInfo = document.getElementById(pageInfoId);
                    if (pageInfo) pageInfo.textContent = 'Showing 0 - 0 of 0 employees';
                }
                return;
            }

            // Calculate pagination
            const totalPages = Math.ceil(totalEmployees / pageSize);
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, totalEmployees);
            const paginatedEmployees = allEmployees.slice(startIndex, endIndex);

            // Clear loading state and populate table
            tableBody.innerHTML = '';

            const escapeHtml = this.wizard.escapeHtml || ((text) => {
                if (text == null) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            });

            paginatedEmployees.forEach(employee => {
                const row = document.createElement('tr');
                const employeeName = employee.name || employee.Name || '';
                const employeeId = employee.Employ_Id || employee.employ_Id || employee.EmployId || employee.employeeId || employee.EmployeeID || employee.employeeID || '';
                const positionName = employee.PositionName || employee.positionName || '-';
                const departmentName = employee.DepartmentName || employee.departmentName || '-';
                
                row.innerHTML = `
                    <td>${escapeHtml(employeeName)}</td>
                    <td>${escapeHtml(positionName)}</td>
                    <td>${escapeHtml(departmentName)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary select-employee-btn" 
                                data-employee-id="${escapeHtml(employeeId || '')}" 
                                data-employee-name="${escapeHtml(employeeName || '')}" 
                                data-target-field="${escapeHtml(targetFieldId || '')}">
                            Select
                        </button>
                    </td>
                `;
                
                // Add event listener to button
                const selectBtn = row.querySelector('.select-employee-btn');
                if (selectBtn) {
                    selectBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const empId = selectBtn.getAttribute('data-employee-id') || '';
                        const empName = selectBtn.getAttribute('data-employee-name') || '';
                        const targetField = selectBtn.getAttribute('data-target-field') || '';
                        if (window.selectUser) {
                            window.selectUser(empId, empName, targetField);
                        }
                    });
                }
                
                tableBody.appendChild(row);
            });

            // Update page info
            if (pageInfoId) {
                const pageInfo = document.getElementById(pageInfoId);
                if (pageInfo) {
                    pageInfo.textContent = `Showing ${startIndex + 1} - ${endIndex} of ${totalEmployees} employees`;
                }
            }

            // Render pagination
            if (paginationId && totalPages > 1) {
                this.renderEmployeePagination(paginationId, currentPage, totalPages, uniqueId, tableBodyId, targetFieldId, pageInfoId);
            } else if (paginationId) {
                const pagination = document.getElementById(paginationId);
                if (pagination) pagination.innerHTML = '';
            }

        } catch (error) {
            console.error('Error loading employees:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        <i class="icon-base bx bx-error-circle me-2"></i>
                        Failed to load employees. Please try again.
                        <br>
                        <small>${this.wizard.escapeHtml ? this.wizard.escapeHtml(error.message) : error.message}</small>
                    </td>
                </tr>
            `;
            if (paginationId) {
                const pagination = document.getElementById(paginationId);
                if (pagination) pagination.innerHTML = '';
            }
            if (pageInfoId) {
                const pageInfo = document.getElementById(pageInfoId);
                if (pageInfo) pageInfo.textContent = 'Showing 0 - 0 of 0 employees';
            }
        }
    }

    /**
     * Render employee pagination
     */
    renderEmployeePagination(paginationId, currentPage, totalPages, uniqueId, tableBodyId, targetFieldId, pageInfoId) {
        const pagination = document.getElementById(paginationId);
        if (!pagination) return;

        pagination.innerHTML = '';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <a class="page-link" href="#" data-page="${currentPage - 1}" data-unique-id="${uniqueId}">
                <i class="icon-base bx bx-chevron-left"></i>
            </a>
        `;
        pagination.appendChild(prevLi);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" data-page="1" data-unique-id="${uniqueId}">1</a>`;
            pagination.appendChild(firstLi);
            
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                pagination.appendChild(ellipsisLi);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}" data-unique-id="${uniqueId}">${i}</a>`;
            pagination.appendChild(pageLi);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                pagination.appendChild(ellipsisLi);
            }
            
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}" data-unique-id="${uniqueId}">${totalPages}</a>`;
            pagination.appendChild(lastLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <a class="page-link" href="#" data-page="${currentPage + 1}" data-unique-id="${uniqueId}">
                <i class="icon-base bx bx-chevron-right"></i>
            </a>
        `;
        pagination.appendChild(nextLi);

        // Add event listeners to pagination links
        pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', async (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && page !== currentPage) {
                    const modalElement = document.getElementById('userSearchModal');
                    if (modalElement) {
                        modalElement.setAttribute('data-current-page', page.toString());
                        const searchInputId = `employeeSearchInput_${uniqueId}`;
                        const searchInput = document.getElementById(searchInputId);
                        const searchTerm = searchInput?.value || null;
                        await this.loadEmployeesToModal(searchTerm, targetFieldId, tableBodyId, paginationId, pageInfoId, uniqueId);
                    }
                }
            });
        });
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardUserSearch = ProcurementWizardUserSearch;
}

