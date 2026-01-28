<script src="/js/procurement/ProcurementSharedCache.js" asp-append-version="true"></script>

<script src="/js/procurement/ProcurementWizardUtils.js" asp-append-version="true"></script>

<script src="/js/procurement/ProcurementWizardAPI.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardBasicInfo.js" asp-append-version="true"></script>

<script src="/js/procurement/ProcurementWizardAdditional.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardItems.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardDocuments.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardApproval.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardSummary.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardSite.js" asp-append-version="true"></script>

<script src="/js/procurement/ProcurementWizardSave.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardValidation.js" asp-append-version="true"></script>

<script src="/js/procurement/ProcurementWizardUserSearch.js" asp-append-version="true"></script>
<script src="/js/procurement/ProcurementWizardGlobalFunctions.js" asp-append-version="true"></script>

<script>
    'use strict';

    // Performance-optimized procurement wizard
    class ProcurementWizard {
        constructor() {
            this.isSearching = false;
            this.stepContents = this.cacheStepContents();
            this.stepOrder = ['basic-information', 'detail', 'assign-approval', 'document', 'summary'];
            this.currentStepIndex = 0;
            this.cachedElements = this.cacheElements();
            this.debounceTimer = null;
            this.searchDebounceTimer = null;
            this.performanceMetrics = {
                startTime: performance.now(),
                stepTransitions: 0,
                domQueries: 0
            };
            // Store File objects for document upload
            this.documentFiles = new Map(); // key: fileName, value: File object
            // Store current Purchase Request Type ID and Sub Type ID to determine if Additional step should be shown
            this.currentPurchaseRequestTypeID = null;
            this.currentPurchaseRequestSubTypeID = null;
            // Site pagination
            this.currentSitePage = 1;
            this.siteSearchDebounceTimer = null;
            this.currentSiteModalMode = 'normal'; // Track which mode the site modal is opened in
            // Billing Types data cache
            this.billingTypes = []; // Store billing types loaded from API
            
            // Initialize modules
            if (typeof ProcurementWizardAPI !== 'undefined') {
                this.apiModule = new ProcurementWizardAPI(this);
            }
            if (typeof ProcurementWizardBasicInfo !== 'undefined') {
                this.basicInfoModule = new ProcurementWizardBasicInfo(this);
            }
            if (typeof ProcurementWizardAdditional !== 'undefined') {
                this.additionalModule = new ProcurementWizardAdditional(this);
            }
            if (typeof ProcurementWizardItems !== 'undefined') {
                this.itemsModule = new ProcurementWizardItems(this);
            }
            if (typeof ProcurementWizardDocuments !== 'undefined') {
                this.documentsModule = new ProcurementWizardDocuments(this);
            }
            if (typeof ProcurementWizardApproval !== 'undefined') {
                this.approvalModule = new ProcurementWizardApproval(this);
            }
            if (typeof ProcurementWizardSummary !== 'undefined') {
                this.summaryModule = new ProcurementWizardSummary(this);
            }
            if (typeof ProcurementWizardSite !== 'undefined') {
                this.siteModule = new ProcurementWizardSite(this);
            }
            if (typeof ProcurementWizardSave !== 'undefined') {
                this.saveModule = new ProcurementWizardSave(this);
            }
            if (typeof ProcurementWizardValidation !== 'undefined') {
                this.validationModule = new ProcurementWizardValidation(this);
            }
            if (typeof ProcurementWizardUserSearch !== 'undefined') {
                this.userSearchModule = new ProcurementWizardUserSearch(this);
            }
            
            this.init();
        }

        cacheStepContents() {
            return {
                'basic-information': document.getElementById('basic-information'),
                'additional': document.getElementById('additional'),
                'detail': document.getElementById('detail'),
                'assign-approval': document.getElementById('assign-approval'),
                'document': document.getElementById('document'),
                'summary': document.getElementById('summary')
            };
        }

        cacheElements() {
            return {
                form: document.getElementById('procurement-wizard-form'),
                stepperContent: document.querySelector('.bs-stepper-content'),
                steps: document.querySelectorAll('.step'),
                amountTotal: document.getElementById('amountTotal'),
                itemDetailTable: document.querySelector('#itemDetailTable tbody'),
                documentTable: document.querySelector('#documentTable tbody')
            };
        }

        async init() {
            this.hideAllSteps();
            this.showStep(this.stepOrder[this.currentStepIndex]);
            this.bindEvents();
            this.updateStepVisuals();
            
            // Load companies, applicants, and purchase types on initialization
            // Wait for all dropdowns to be populated before checking for existing PR
            if (this.apiModule) {
                await Promise.all([
                    this.apiModule.loadCompaniesFromApi(),
                    this.apiModule.loadApplicantsFromApi(),
                    this.apiModule.loadPurchaseTypesFromApi()
                ]);
            } else {
                // Fallback to direct calls if module not available
                await Promise.all([
                    this.loadCompaniesFromApi(),
                    this.loadApplicantsFromApi(),
                    this.loadPurchaseTypesFromApi()
                ]);
            }
            
            // Check if we need to load existing PR data (for editing draft PR)
            // This will only run after all dropdowns are populated
            await this.checkAndLoadExistingPR();
        }

        async checkAndLoadExistingPR() {
            try {
                // Check URL parameter first
                const urlParams = new URLSearchParams(window.location.search);
                const prNumberFromUrl = urlParams.get('prNumber');
                
                // If no prNumber in URL, this is a new Create PR - clear draft state
                if (!prNumberFromUrl) {
                    localStorage.removeItem('procurementDraft');
                    localStorage.removeItem('procurementDraftItemsSaved');
                    localStorage.removeItem('procurementDraftApprovalSaved');
                    localStorage.removeItem('procurementDraftDocumentsSaved');
                    return;
                }
                
                // Check localStorage
                const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                const prNumber = prNumberFromUrl || draft.purchReqNumber;
                const loadExisting = draft.loadExisting || !!prNumberFromUrl;
                
                if (loadExisting && prNumber) {
                    await this.loadExistingPRData(prNumber);
                }
            } catch (error) {
                console.error('Error checking for existing PR:', error);
                // On error, clear draft state to prevent issues
                localStorage.removeItem('procurementDraft');
            }
        }

        async loadExistingPRData(prNumber) {
            try {
                
                // Step 1: Load PR basic information
                const prData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
                const pr = prData.data || prData;
                
                if (!pr) {
                    console.error('PR data not found');
                    return;
                }
                
                // Check if mstApprovalStatusID == 5 (Rejected) or 6 (Draft)
                const statusID = pr.mstApprovalStatusID || pr.MstApprovalStatusID;
                if (statusID !== 5 && statusID !== 6) {
                    return;
                }
                
                // Store status ID in wizard instance for use in other modules
                this.currentApprovalStatusID = statusID;
                
                // Step 2: Auto-fill Basic Information form (wait for dropdowns to be populated)
                if (this.basicInfoModule) {
                    await this.basicInfoModule.fillBasicInformationForm(pr);
                } else {
                    await this.fillBasicInformationForm(pr);
                }
                
                // Wait a bit to ensure Type and Sub Type are fully set (reduced from 300ms to 100ms)
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Step 3-6: Load Additional, Items, Documents, and Approval in parallel for better performance
                // Note: Additional must be loaded after Basic Info to know which section to show
                // Approval can be loaded in parallel since it only needs PR data
                const loadPromises = [];
                
                // Load Additional data (must be after Basic Info to know which section to show)
                if (this.additionalModule) {
                    loadPromises.push(this.additionalModule.loadAndFillAdditional(prNumber));
                } else {
                    loadPromises.push(this.loadAndFillAdditional(prNumber));
                }
                
                // Load Items in parallel
                if (this.itemsModule) {
                    loadPromises.push(this.itemsModule.loadAndFillItems(prNumber));
                } else {
                    loadPromises.push(this.loadAndFillItems(prNumber));
                }
                
                // Load Documents in parallel
                if (this.documentsModule) {
                    loadPromises.push(this.documentsModule.loadAndFillDocuments(prNumber));
                } else {
                    loadPromises.push(this.loadAndFillDocuments(prNumber));
                }
                
                // Load Approval in parallel (it only needs PR data, not dependent on other data)
                if (this.apiModule) {
                    loadPromises.push(this.apiModule.fillApprovalForm(pr));
                } else {
                    loadPromises.push(this.fillApprovalForm(pr));
                }
                
                // Wait for all parallel loads to complete
                await Promise.all(loadPromises);
                
                // Step 7: If status is 5 (Rejected), make Basic Information readonly
                // Note: For status 5, ReviewedBy, ApprovedBy, and ConfirmedBy should remain editable
                if (statusID === 5) {
                    this.setBasicInformationReadonly();
                    this.setAssignApprovalReadonlyForRejected();
                    // Set Additional fields to readonly, but keep StartPeriod and Period editable
                    // Pass PR data to check if Sonumb is null (if null, enable field for revision)
                    this.setAdditionalReadonlyForRejected(pr);
                }
                
                // Step 8: Update summary after all fields are filled (especially important for status 5)
                this.updateSummary();
                
                // Store PR Number in localStorage
                localStorage.setItem('procurementDraft', JSON.stringify({
                    id: pr.id || pr.ID,
                    purchReqNumber: prNumber,
                    company: pr.company || pr.Company,
                    createdDate: pr.createdDate || pr.CreatedDate,
                    loadExisting: true,
                    statusID: statusID
                }));
                
            } catch (error) {
                console.error('Error loading existing PR data:', error);
                this.showValidationMessage('Error loading PR data: ' + error.message);
            }
        }

        async fillBasicInformationForm(pr) {
            if (this.basicInfoModule) {
                return this.basicInfoModule.fillBasicInformationForm(pr);
            }
            console.warn('ProcurementWizardBasicInfo module not available');
        }

        waitForDropdownPopulated(selectElement, callback, maxWait = 5000) {
            return new Promise(async (resolve) => {
                if (!selectElement) {
                    resolve();
                    return;
                }
                
                const startTime = Date.now();
                
                const checkDropdown = async () => {
                    // Check if dropdown has options (more than just the default option)
                    const hasOptions = selectElement.options.length > 1 || 
                                     (selectElement.options.length === 1 && selectElement.options[0].value !== '');
                    
                    if (hasOptions) {
                        // Dropdown is populated, execute callback
                        if (callback) {
                            try {
                                await callback();
                            } catch (error) {
                                console.error('Error in dropdown callback:', error);
                            }
                        }
                        resolve();
                    } else {
                        // Check if we've exceeded max wait time
                        const elapsed = Date.now() - startTime;
                        if (elapsed >= maxWait) {
                            console.warn(`Timeout waiting for dropdown ${selectElement.id} to populate`);
                            if (callback) {
                                try {
                                    await callback();
                                } catch (error) {
                                    console.error('Error in dropdown callback:', error);
                                }
                            }
                            resolve();
                        } else {
                            // Wait a bit more and check again
                            setTimeout(checkDropdown, 100);
                        }
                    }
                };
                
                checkDropdown();
            });
        }

        /**
         * Helper function to check if Type ID and SubType ID require Additional Section
         * Parameters: typeId (number) - Purchase Request Type ID, subTypeId (number|null) - Purchase Request SubType ID (can be null for Type ID 5 or 7)
         * Returns: boolean - True if Additional Section is required
         */
        requiresAdditionalSection(typeId, subTypeId) {
            if (!typeId) return false;
            
            // Type ID 5 or 7: Subscribe section (no Sub Type required)
            if (typeId === 5 || typeId === 7) return true;
            
            // Type ID 6 && Sub Type ID 2: Billing Type section
            if (typeId === 6 && subTypeId === 2) return true;
            
            // Sonumb Section conditions
            // Type ID 8 && Sub Type ID 4
            if (typeId === 8 && subTypeId === 4) return true;
            // Type ID 2 && (Sub Type ID 1 || 3)
            if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
            // Type ID 4 && Sub Type ID 3
            if (typeId === 4 && subTypeId === 3) return true;
            // Type ID 3 && (Sub Type ID 4 || 5)
            if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
            
            return false;
        }

        async loadAndFillAdditional(prNumber) {
            if (this.additionalModule && typeof this.additionalModule.loadAndFillAdditional === 'function') {
                return this.additionalModule.loadAndFillAdditional(prNumber);
            }
            console.warn('ProcurementWizardAdditional module or loadAndFillAdditional method not available');
        }

        async loadAndFillItems(prNumber) {
            if (this.itemsModule && typeof this.itemsModule.loadAndFillItems === 'function') {
                return this.itemsModule.loadAndFillItems(prNumber);
            }
            console.warn('ProcurementWizardItems module or loadAndFillItems method not available');
        }

        async fillApprovalForm(pr) {
            if (this.apiModule && typeof this.apiModule.fillApprovalForm === 'function') {
                return this.apiModule.fillApprovalForm(pr);
            }
            console.warn('ProcurementWizardAPI module or fillApprovalForm method not available');
        }

        async getEmployeeNameByEmployId(employId) {
            if (this.apiModule) {
                return this.apiModule.getEmployeeNameByEmployId(employId);
            }
            console.warn('ProcurementWizardAPI module not available');
            return '';
        }

        setBasicInformationReadonly() {
            if (this.basicInfoModule) {
                return this.basicInfoModule.setBasicInformationReadonly();
            }
            console.warn('ProcurementWizardBasicInfo module not available');
        }

        // Set Assign Approval fields to readonly (for status 5 - Rejected)
        // Note: ReviewedBy, ApprovedBy, and ConfirmedBy remain editable for status 5
        setAssignApprovalReadonlyForRejected() {
            if (this.approvalModule && typeof this.approvalModule.setAssignApprovalReadonlyForRejected === 'function') {
                return this.approvalModule.setAssignApprovalReadonlyForRejected();
            }
            console.warn('ProcurementWizardApproval module or setAssignApprovalReadonlyForRejected method not available');
        }

        // Set Additional fields to readonly (for status 5 - Rejected)
        // Delegated to Additional module
        setAdditionalReadonlyForRejected(prData = null) {
            if (this.additionalModule && typeof this.additionalModule.setAdditionalReadonlyForRejected === 'function') {
                return this.additionalModule.setAdditionalReadonlyForRejected(prData);
            }
            console.warn('ProcurementWizardAdditional module or setAdditionalReadonlyForRejected method not available');
        }

        async loadAndFillDocuments(prNumber) {
            if (this.documentsModule && typeof this.documentsModule.loadAndFillDocuments === 'function') {
                return this.documentsModule.loadAndFillDocuments(prNumber);
            }
            console.warn('ProcurementWizardDocuments module or loadAndFillDocuments method not available');
        }

        createDocumentTableRow(doc) {
            if (this.documentsModule && typeof this.documentsModule.createDocumentTableRow === 'function') {
                return this.documentsModule.createDocumentTableRow(doc);
            }
            console.warn('ProcurementWizardDocuments module or createDocumentTableRow method not available');
            return null;
        }

        bindEvents() {
            // Single event listener for all button clicks - performance optimization
            document.addEventListener('click', this.handleClick.bind(this));
            
            // Form validation on input
            if (this.cachedElements.form) {
                this.cachedElements.form.addEventListener('input', this.handleInputValidation.bind(this));
            }
            
            // Setup field validation listeners to clear error messages when fields are filled
            this.setupFieldValidationListeners();

            // Rows per page change handlers
            const rowsPerPageSelect = document.getElementById('rowsPerPage');
            if (rowsPerPageSelect) {
                rowsPerPageSelect.addEventListener('change', (e) => {
                    const rowsPerPage = parseInt(e.target.value);
                    const totalItems = this.getTotalDetailItems();
                    this.updateDetailPagination(totalItems, 1, rowsPerPage);
                });
            }

            const chooseItemRowsPerPageSelect = document.getElementById('chooseItemRowsPerPage');
            if (chooseItemRowsPerPageSelect) {
                chooseItemRowsPerPageSelect.addEventListener('change', (e) => {
                    const rowsPerPage = parseInt(e.target.value);
                    const totalItems = this.getTotalChooseItems();
                    this.updateChooseItemPagination(totalItems, 1, rowsPerPage);
                    this.updateChooseItemTableVisibility(1, rowsPerPage);
                });
            }

            const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
            if (documentRowsPerPageSelect) {
                documentRowsPerPageSelect.addEventListener('change', (e) => {
                    const rowsPerPage = parseInt(e.target.value);
                    const totalItems = this.getTotalDocumentItems();
                    this.updateDocumentPagination(totalItems, 1, rowsPerPage);
                });
            }

            // Cancel Create PR is handled by ProcurementWizardGlobalFunctions.js

            // Initialize Billing Type dropdown
            if (this.apiModule) {
                this.apiModule.loadBillingTypesFromApi();
            } else {
                this.loadBillingTypesFromApi();
            }
            if (this.additionalModule) {
                this.additionalModule.initializeBillingTypeDropdown();
            } else {
                this.initializeBillingTypeDropdown();
            }

            // Initialize date change listeners for Qty calculation
            this.initializeAdditionalStepListeners();
            
            // Initialize Choose Site modal
            this.initializeChooseSiteModal();
            
            // Initialize Subscribe section
            this.initializeSubscribeBillingTypeDropdown();
            this.initializeSubscribeStepListeners();
        }

        async loadBillingTypesFromApi() {
            if (this.apiModule) {
                return this.apiModule.loadBillingTypesFromApi();
            }
            console.warn('ProcurementWizardAPI module not available');
        }

        initializeBillingTypeDropdown() {
            if (this.additionalModule) {
                return this.additionalModule.initializeBillingTypeDropdown();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }

        initializeAdditionalStepListeners() {
            if (this.additionalModule) {
                return this.additionalModule.initializeAdditionalStepListeners();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }

        calculateEndPeriod() {
            if (this.additionalModule) {
                return this.additionalModule.calculateEndPeriod();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        initializeSubscribeYearDropdowns() {
            if (this.additionalModule) {
                return this.additionalModule.initializeSubscribeYearDropdowns();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        initializeSubscribeBillingTypeDropdown() {
            if (this.additionalModule) {
                return this.additionalModule.initializeSubscribeBillingTypeDropdown();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        initializeSubscribeStepListeners() {
            if (this.additionalModule) {
                return this.additionalModule.initializeSubscribeStepListeners();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        updateSubscribeDate(type) {
            if (this.additionalModule) {
                return this.additionalModule.updateSubscribeDate(type);
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        validateSubscribeDateRange() {
            if (this.additionalModule) {
                return this.additionalModule.validateSubscribeDateRange();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }
        
        calculateSubscribeEndPeriod() {
            if (this.additionalModule) {
                return this.additionalModule.calculateSubscribeEndPeriod();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }

        calculateSubscribeQty() {
            if (this.additionalModule) {
                return this.additionalModule.calculateSubscribeQty();
            }
            console.warn('ProcurementWizardAdditional module not available');
        }

        initializeChooseSiteModal() {
            if (this.siteModule) {
                return this.siteModule.initializeChooseSiteModal();
            }
            console.warn('ProcurementWizardSite module not available');
        }

        openChooseSiteModal(mode = 'normal') {
            if (this.siteModule) {
                return this.siteModule.openChooseSiteModal(mode);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        async loadSites() {
            if (this.siteModule) {
                return this.siteModule.loadSites();
            }
            console.warn('ProcurementWizardSite module not available');
        }

        selectSite(id, sonumb, siteId, siteName) {
            if (this.siteModule) {
                return this.siteModule.selectSite(id, sonumb, siteId, siteName);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        async validateItemIdWithSonumb(sonumbValue, mode = 'normal') {
            if (this.siteModule) {
                return this.siteModule.validateItemIdWithSonumb(sonumbValue, mode);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        updateSitePagination(totalItems, currentPage, rowsPerPage) {
            if (this.siteModule) {
                return this.siteModule.updateSitePagination(totalItems, currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        goToSitePage(page) {
            if (this.siteModule) {
                return this.siteModule.goToSitePage(page);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        debounceSearch(callback) {
            if (this.siteModule) {
                return this.siteModule.debounceSearch(callback);
            }
            console.warn('ProcurementWizardSite module not available');
        }

        async handleClick(e) {
            // Skip handling if click is inside a modal (to prevent conflicts with modal's own event handlers)
            if (e.target.closest('.modal')) {
                return;
            }
            
            // Find the button element - handle cases where click might be on icon/text inside button
            let target = e.target.closest('button, [role="button"]');
            
            // If target is not a button, try to find parent button
            if (!target || (target.tagName !== 'BUTTON' && target.getAttribute('role') !== 'button')) {
                // Check if clicked element is inside a button (e.g., icon or span)
                const parentButton = e.target.closest('button');
                if (parentButton) {
                    target = parentButton;
                } else {
                    return; // Not a button click
                }
            }

            const buttonId = target.id;
            const buttonClass = target.className;

            e.preventDefault();
            e.stopPropagation();

            // Consolidated button handling
            if (buttonClass.includes('btn-next')) {
                this.handleNext();
            } else if (buttonClass.includes('btn-prev')) {
                this.handlePrevious();
            } else if (buttonClass.includes('btn-submit')) {
                this.handleSubmit();
             } else if (buttonId === 'saveDraftBtn') {
                 // Check which step we're on
                 const currentStepId = this.stepOrder[this.currentStepIndex];
                 if (currentStepId === 'detail') {
                     // Save Basic Info + Items
                     this.saveDraftItems();
                 } else if (currentStepId === 'summary') {
                     // Save all: Basic Info + Items + Approval + Documents
                     this.saveDraftAll();
                 } else {
                     // Save basic info only
                     this.saveDraftBasicInfo();
                 }
             } else if (buttonId === 'saveDraftDocumentBtn') {
                 // Save Basic Info + Items + Approval + Documents
                 this.saveDraftDocuments();
             } else if (buttonId === 'saveDraftApprovalBtn') {
                 // Save Basic Info + Items + Approval
                 this.saveDraftApproval().catch(error => {
                     console.error('Error in saveDraftApproval:', error);
                     this.showValidationMessage('Error saving draft approval: ' + error.message);
                 });
            } else if (buttonId === 'saveDraftAdditionalBtn') {
                // Save Additional data
                this.saveDraftAdditional().catch(error => {
                    console.error('Error in saveDraftAdditional:', error);
                    this.showValidationMessage('Error saving draft additional: ' + error.message);
                });
             } else if (buttonId === 'submitPRBtn') {
                 // Submit PR: Save Basic Info + Items + Approval + Documents
                 this.submitPR().catch(error => {
                     console.error('Error in submitPR:', error);
                     this.showValidationMessage('Error submitting PR: ' + error.message);
                 });
            } else if (buttonId === 'addDetailBtn') {
                this.showAddItemForm();
            } else if (buttonId === 'backToDetailBtn') {
                this.showDetailTable();
            } else if (buttonId === 'addItemBtn') {
                // Pass the button element so we can find the form relative to it
                await this.addNewItemToTable(target);
            } else if (buttonId === 'searchItemBtn') {
                this.showChooseItemTable();
            } else if (buttonId === 'searchItemNameBtn' || buttonId === 'searchItemIdBtn' || buttonId === 'searchProdPoolBtn' || buttonId === 'searchCOABtn') {
                // Search items by any field
                this.searchItems();
            } else if (buttonId === 'clearSearchBtn') {
                // Clear all search fields
                document.getElementById('searchItemId').value = '';
                document.getElementById('searchItemName').value = '';
                document.getElementById('searchProdPool').value = '';
                document.getElementById('searchCOA').value = '';
                this.loadItemsFromApi();
            } else if (buttonId === 'backToAddItemBtn') {
                this.showAddItemForm();
            } else if (buttonId === 'addDocumentBtn') {
                this.openFileUploadDialog();
            } else if (buttonId.includes('search') && buttonId.includes('Btn') && !buttonId.includes('Item') && !buttonId.includes('STIP') && !buttonId.includes('Subscribe')) {
                // Determine which field is being searched
                // Exclude STIP search buttons (searchSTIPSonumbBtn, searchSubscribeSonumbBtn) - they have their own handler
                let targetFieldId = null;
                if (buttonId.includes('ReviewedBy')) {
                    targetFieldId = 'reviewedBy';
                } else if (buttonId.includes('ApprovedBy')) {
                    targetFieldId = 'approvedBy';
                } else if (buttonId.includes('ConfirmedBy')) {
                    targetFieldId = 'confirmedBy';
                }
                this.openUserSearchModal(null, targetFieldId);
            } else if (buttonId.includes('view') && buttonId.includes('HistoryBtn')) {
                this.handleViewHistory(buttonId);
            }
        }

        // Navigate to step with invalid field and focus on it
        navigateToInvalidField(invalidFieldInfo) {
            if (!invalidFieldInfo || !invalidFieldInfo.step) return;
            
            const targetStepId = invalidFieldInfo.step;
            const targetFieldId = invalidFieldInfo.fieldId;
            const targetField = invalidFieldInfo.field;
            
            // Find step index
            const stepIndex = this.stepOrder.indexOf(targetStepId);
            if (stepIndex === -1) return;
            
            // Navigate to the step
            this.currentStepIndex = stepIndex;
            this.hideAllSteps();
            this.showStep(targetStepId);
            this.updateStepVisuals();
            
            // Wait a bit for step to be visible, then focus on field
            setTimeout(() => {
                // Special handling for "At least one required" errors - just navigate to step, no field focus needed
                if ((targetStepId === 'detail' && targetFieldId === 'addDetailBtn' && !targetField) ||
                    (targetStepId === 'document' && targetFieldId === 'addDocumentBtn' && !targetField)) {
                    // For "At least one item/document required" errors, just scroll to step content
                    const stepContent = this.stepContents[targetStepId];
                    if (stepContent) {
                        stepContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    return; // Exit early, no need to focus on any field
                }
                
                // Special handling for empty items/documents - show add form
                if (targetStepId === 'detail' && targetFieldId === 'addDetailBtn') {
                    // Show add item form if no items exist
                    this.showAddItemForm();
                } else if (targetStepId === 'detail' && targetField && targetField.tagName === 'TR') {
                    // For invalid item rows, ensure detail table is shown (not add form)
                    this.showDetailTable();
                } else if (targetStepId === 'document' && targetFieldId === 'addDocumentBtn') {
                    // Open file upload dialog if no documents exist
                    this.openFileUploadDialog();
                }
                
                let fieldToFocus = null;
                
                if (targetField) {
                    // If field element is provided, use it directly
                    fieldToFocus = targetField;
                } else if (targetFieldId) {
                    // Try to find field by ID
                    fieldToFocus = document.getElementById(targetFieldId);
                    
                    // If not found and it's a button, try to find it
                    if (!fieldToFocus && (targetFieldId === 'addDetailBtn' || targetFieldId === 'addDocumentBtn')) {
                        fieldToFocus = document.getElementById(targetFieldId);
                    }
                }
                
                if (fieldToFocus) {
                    // Scroll to field and focus
                    fieldToFocus.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // For input/textarea/select, focus directly
                    if (fieldToFocus.tagName === 'INPUT' || fieldToFocus.tagName === 'TEXTAREA' || fieldToFocus.tagName === 'SELECT') {
                        setTimeout(() => {
                            fieldToFocus.focus();
                            // Add visual highlight
                            fieldToFocus.classList.add('is-invalid');
                        }, 100);
                    } else if (fieldToFocus.tagName === 'BUTTON') {
                        // For buttons, focus and add visual highlight if it's a dropdown button
                        setTimeout(() => {
                            fieldToFocus.focus();
                            // Add visual highlight for dropdown buttons
                            if (fieldToFocus.id && (fieldToFocus.id.includes('DropdownBtn') || fieldToFocus.id.includes('search') || fieldToFocus.id.includes('add'))) {
                                fieldToFocus.classList.add('is-invalid');
                            }
                        }, 100);
                    } else if (fieldToFocus.tagName === 'TR') {
                        // For table rows, scroll to row and highlight it
                        setTimeout(() => {
                            fieldToFocus.classList.add('table-danger');
                            // If it's an item row, try to focus on edit button
                            const editBtn = fieldToFocus.querySelector('button[title="Edit"]');
                            if (editBtn) {
                                editBtn.focus();
                            }
                        }, 100);
                    }
                } else {
                    // If field not found, try to find by fieldId mapping for custom dropdowns
                    const stepContent = this.stepContents[targetStepId];
                    if (stepContent && targetFieldId) {
                        let dropdownBtn = null;
                        
                        // Map field IDs to dropdown buttons
                        if (targetFieldId === 'Applicant') {
                            dropdownBtn = stepContent.querySelector('#applicantDropdownBtn');
                        } else if (targetFieldId === 'Company') {
                            dropdownBtn = stepContent.querySelector('#companyDropdownBtn');
                        } else if (targetFieldId === 'PurchaseRequestType') {
                            dropdownBtn = stepContent.querySelector('#purchaseRequestTypeDropdownBtn');
                        } else if (targetFieldId === 'PurchaseRequestSubType') {
                            dropdownBtn = stepContent.querySelector('#purchaseRequestSubTypeDropdownBtn');
                        } else if (targetFieldId === 'reviewedBy') {
                            // For approval fields, focus on the search button
                            dropdownBtn = stepContent.querySelector('#searchReviewedByBtn');
                        } else if (targetFieldId === 'approvedBy') {
                            dropdownBtn = stepContent.querySelector('#searchApprovedByBtn');
                        } else if (targetFieldId === 'confirmedBy') {
                            dropdownBtn = stepContent.querySelector('#searchConfirmedByBtn');
                        } else if (targetFieldId === 'addDetailBtn') {
                            dropdownBtn = stepContent.querySelector('#addDetailBtn');
                        } else if (targetFieldId === 'addDocumentBtn') {
                            dropdownBtn = stepContent.querySelector('#addDocumentBtn');
                        }
                        
                        if (dropdownBtn) {
                            dropdownBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            setTimeout(() => {
                                dropdownBtn.focus();
                                dropdownBtn.classList.add('is-invalid');
                            }, 100);
                        } else {
                            // If still not found, just scroll to step content
                            stepContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else {
                        // If step content not found, just scroll to top
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }, 300);
        }

        handleNext() {
            const currentStepId = this.stepOrder[this.currentStepIndex];
            
            if (currentStepId === 'basic-information') {
                if (this.basicInfoModule) {
                    if (!this.basicInfoModule.validateBasicInformationStep()) {
                        return;
                    }
                } else if (!this.validateBasicInformationStep()) {
                    return;
                }
            }
            
            if (currentStepId === 'additional' && !this.validateAdditionalStep()) {
                return;
            }
            
            // Validate assign-approval step
            if (currentStepId === 'assign-approval') {
                if (this.validationModule && this.validationModule.validateApprovalStep) {
                    if (!this.validationModule.validateApprovalStep()) {
                        return;
                    }
                }
            }
            
            this.moveToNextStep();
        }

        handlePrevious() {
            this.moveToPreviousStep();
        }

        handleSubmit() {
            this.updateSummary();
            this.showSuccessMessage();
        }

        async handleViewHistory(buttonId) {
            // Extract field ID from button ID
            // Button ID format: viewReviewedByHistoryBtn, viewApprovedByHistoryBtn, viewConfirmedByHistoryBtn
            // Field ID format: ReviewedBy, ApprovedBy, ConfirmedBy
            let assignApproval = buttonId.replace('view', '').replace('HistoryBtn', '');
            
            // Map to AssignApproval values: Reviewed, Approved, Confirmed
            const assignApprovalMap = {
                'ReviewedBy': 'Reviewed',
                'ApprovedBy': 'Approved',
                'ConfirmedBy': 'Confirmed'
            };
            
            const assignApprovalValue = assignApprovalMap[assignApproval] || assignApproval;
            
            // Get PR Number from localStorage or URL
            const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            const prNumber = draft.purchReqNumber;
            
            if (!prNumber) {
                this.showValidationMessage('PR Number not found. Please save the PR first.');
                return;
            }
            
            // Delegate to Approval module
            if (this.approvalModule) {
                await this.approvalModule.showApprovalPICHistoryModal(prNumber, assignApprovalValue);
            } else {
                // Module not available - log warning
                console.warn('ProcurementWizardApproval module not available');
            }
        }

        escapeHtml(text) {
            // Use utility module if available, otherwise fallback to original implementation
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.escapeHtml(text);
            }
            // Fallback for backward compatibility
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        handleInputValidation(e) {
            // Debounce validation for better performance
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                if (e.target.hasAttribute('required')) {
                    const isValid = e.target.value.trim().length > 0;
                    e.target.classList.toggle('is-invalid', !isValid);
                    e.target.classList.toggle('is-valid', isValid);
                }
                
                // Removed auto-search for searchItemName - search only happens on button click or Enter key
            }, 300);
        }

        hideAllSteps() {
            Object.values(this.stepContents).forEach(content => {
                if (content) {
                    content.style.display = 'none';
                }
            });
        }

        showStep(stepId) {
            const stepContent = this.stepContents[stepId];
            if (stepContent) {
                stepContent.style.display = 'block';
            }
            
            // Initialize amount total and pagination when showing detail step
            if (stepId === 'detail') {
                this.updateAmountTotal();
                // Initialize pagination for detail table
                const rowsPerPageSelect = document.getElementById('rowsPerPage');
                const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
                const totalItems = this.getTotalDetailItems();
                this.updateDetailPagination(totalItems, 1, rowsPerPage);
            }
            
            // Initialize pagination when showing document step
            if (stepId === 'document') {
                const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
                const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
                const totalItems = this.getTotalDocumentItems();
                this.updateDocumentPagination(totalItems, 1, rowsPerPage);
            }
        }

        moveToNextStep() {
            if (this.currentStepIndex < this.stepOrder.length - 1) {
                this.performanceMetrics.stepTransitions++;
                this.currentStepIndex++;
                this.hideAllSteps();
                const nextStepId = this.stepOrder[this.currentStepIndex];
                this.showStep(nextStepId);
                
                // Auto-fill applicant when entering assign-approval step
                if (nextStepId === 'assign-approval') {
                    this.autoFillApplicantFromBasicInfo();
                }
                
                // Update summary when navigating to summary step
                if (nextStepId === 'summary') {
                    this.updateSummary();
                    // Initialize confirmation checkbox and submit button
                    this.initializeSubmitConfirmation();
                }
                
                this.updateStepVisuals();
                this.logPerformanceMetrics();
            }
        }

        moveToPreviousStep() {
            if (this.currentStepIndex > 0) {
                this.currentStepIndex--;
                this.hideAllSteps();
                this.showStep(this.stepOrder[this.currentStepIndex]);
                this.updateStepVisuals();
            }
        }

        setupFieldValidationListeners() {
            // Setup event listeners to clear error messages when fields are filled
            // Use event delegation for better performance
            document.addEventListener('input', (e) => {
                const field = e.target;
                if (field && (field.hasAttribute('required') || field.classList.contains('is-invalid'))) {
                    const fieldValue = field.value || '';
                    if (fieldValue.trim() !== '') {
                        // Field is now filled, remove error
                        field.classList.remove('is-invalid');
                        if (this.validationModule && this.validationModule.hideFieldError) {
                            this.validationModule.hideFieldError(field);
                        }
                    }
                }
            });

            // Handle change events for dropdowns and selects
            document.addEventListener('change', (e) => {
                const field = e.target;
                if (field && (field.hasAttribute('required') || field.classList.contains('is-invalid'))) {
                    const fieldValue = field.value || '';
                    if (fieldValue.trim() !== '') {
                        // Field is now filled, remove error
                        field.classList.remove('is-invalid');
                        if (this.validationModule && this.validationModule.hideFieldError) {
                            this.validationModule.hideFieldError(field);
                        }
                    }
                }
            });

            // Handle custom dropdown selections (for hidden inputs)
            const customDropdowns = ['Applicant', 'Company', 'PurchaseRequestType', 'PurchaseRequestSubType', 'BillingType', 'SubscribeBillingType', 'unit', 'currency'];
            customDropdowns.forEach(fieldId => {
                const hiddenInput = document.getElementById(fieldId);
                if (hiddenInput) {
                    hiddenInput.addEventListener('change', () => {
                        const fieldValue = hiddenInput.value || '';
                        if (fieldValue.trim() !== '') {
                            // Field is now filled, remove error
                            hiddenInput.classList.remove('is-invalid');
                            
                            // Find dropdown button - handle different naming conventions
                            let dropdownBtn = null;
                            if (fieldId === 'BillingType') {
                                dropdownBtn = document.getElementById('billingTypeDropdownBtn');
                            } else if (fieldId === 'SubscribeBillingType') {
                                dropdownBtn = document.getElementById('subscribeBillingTypeDropdownBtn');
                            } else if (fieldId === 'unit') {
                                dropdownBtn = document.getElementById('unitDropdownBtn');
                            } else if (fieldId === 'currency') {
                                dropdownBtn = document.getElementById('currencyDropdownBtn');
                            } else {
                                dropdownBtn = document.getElementById(fieldId.toLowerCase() + 'DropdownBtn') || 
                                               document.getElementById(fieldId.replace(/([A-Z])/g, '-$1').toLowerCase() + '-dropdown-btn');
                            }
                            
                            if (dropdownBtn) {
                                dropdownBtn.classList.remove('is-invalid');
                            }
                            if (this.validationModule && this.validationModule.hideFieldError) {
                                this.validationModule.hideFieldError(hiddenInput);
                                if (dropdownBtn) {
                                    this.validationModule.hideFieldError(dropdownBtn);
                                }
                            }
                            
                            // Check if Additional step should be shown when PurchaseRequestType or PurchaseRequestSubType changes
                            if (fieldId === 'PurchaseRequestType' || fieldId === 'PurchaseRequestSubType') {
                                // Update current Type/SubType ID
                                if (fieldId === 'PurchaseRequestType') {
                                    this.currentPurchaseRequestTypeID = fieldValue ? parseInt(fieldValue) : null;
                                    // Clear Sub Type when Type changes
                                    const purchaseRequestSubTypeField = document.getElementById('PurchaseRequestSubType');
                                    if (purchaseRequestSubTypeField) {
                                        purchaseRequestSubTypeField.value = '';
                                    }
                                    this.currentPurchaseRequestSubTypeID = null;
                                } else if (fieldId === 'PurchaseRequestSubType') {
                                    this.currentPurchaseRequestSubTypeID = fieldValue ? parseInt(fieldValue) : null;
                                }
                                
                                // Check and toggle Additional step
                                if (this.checkAndToggleAdditionalStep) {
                                    this.checkAndToggleAdditionalStep();
                                }
                            }
                        }
                    });
                }
            });
            
            // Handle approval fields (reviewedById, approvedById, confirmedById) - remove error when employee is selected
            const approvalFields = ['reviewedById', 'approvedById', 'confirmedById'];
            approvalFields.forEach(hiddenFieldId => {
                const hiddenInput = document.getElementById(hiddenFieldId);
                if (hiddenInput) {
                    hiddenInput.addEventListener('change', () => {
                        const fieldValue = hiddenInput.value || '';
                        if (fieldValue.trim() !== '') {
                            // Field is now filled, remove error
                            hiddenInput.classList.remove('is-invalid');
                            
                            // Find corresponding visible field (e.g., reviewedById -> reviewedBy)
                            const visibleFieldId = hiddenFieldId.replace('Id', '');
                            const visibleField = document.getElementById(visibleFieldId);
                            
                            if (visibleField) {
                                visibleField.classList.remove('is-invalid');
                            }
                            
                            // Remove error messages
                            if (this.validationModule && this.validationModule.hideFieldError) {
                                this.validationModule.hideFieldError(hiddenInput);
                                if (visibleField) {
                                    this.validationModule.hideFieldError(visibleField);
                                }
                            }
                        }
                    });
                }
            });
                }

        validateAdditionalStep() {
            // Delegate to Additional module
            if (this.additionalModule) {
                return this.additionalModule.validateAdditionalStep();
            }
            // Module not available - return false as fallback
            console.warn('ProcurementWizardAdditional module not available');
            return false;
        }


        updateStepVisuals() {
            // Get all visible steps from DOM (excluding hidden Additional step if not in stepOrder)
            const allSteps = document.querySelectorAll('.bs-stepper-header .step');
            
            // Create a map of step data-target to stepOrder index
            const stepOrderMap = {};
            this.stepOrder.forEach((stepId, orderIndex) => {
                stepOrderMap[stepId] = orderIndex;
            });
            
            // Update each step with dynamic numbering based on stepOrder
            allSteps.forEach((step, domIndex) => {
                const stepIcon = step.querySelector('.bs-stepper-icon svg use');
                if (!stepIcon) return;

                // Get the data-target attribute to identify which step this is
                const dataTarget = step.getAttribute('data-target');
                if (!dataTarget) return;
                
                // Extract step ID from data-target (e.g., "#basic-information" -> "basic-information")
                const stepId = dataTarget.replace('#', '');
                
                // Skip if this step is not in the current stepOrder (e.g., hidden Additional step)
                if (!stepOrderMap.hasOwnProperty(stepId)) {
                    // Hide this step if it's not in stepOrder
                    step.style.display = 'none';
                    return;
                }
                
                // Show this step if it's in stepOrder
                step.style.display = '';
                
                // Get the step number based on its position in stepOrder (1-based)
                const stepNumber = stepOrderMap[stepId] + 1;
                
                // Check if this step is completed or active based on currentStepIndex
                const isCompleted = stepOrderMap[stepId] < this.currentStepIndex;
                const isActive = stepOrderMap[stepId] === this.currentStepIndex;
                
                // Update icon with dynamic step number
                const iconPath = isCompleted || isActive 
                    ? `/assets/svg/icons/wizard-step-${stepNumber}-active.svg#wizardStep${stepNumber}Active`
                    : `/assets/svg/icons/wizard-step-${stepNumber}.svg#wizardStep${stepNumber}`;
                
                stepIcon.setAttribute('xlink:href', iconPath);
                
                // Update classes efficiently
                step.classList.toggle('completed', isCompleted);
                step.classList.toggle('active', isActive);
            });
        }

        toggleAdditionalStep(showAdditional) {
            // Update stepOrder based on whether Additional step should be shown
            if (showAdditional) {
                // Include 'additional' step between 'basic-information' and 'detail'
                if (!this.stepOrder.includes('additional')) {
                    const basicInfoIndex = this.stepOrder.indexOf('basic-information');
                    this.stepOrder.splice(basicInfoIndex + 1, 0, 'additional');
                }
            } else {
                // Remove 'additional' step from stepOrder
                const additionalIndex = this.stepOrder.indexOf('additional');
                if (additionalIndex !== -1) {
                    // If we're currently on the additional step, move to previous step
                    if (this.currentStepIndex === additionalIndex) {
                        this.currentStepIndex = Math.max(0, additionalIndex - 1);
                    } else if (this.currentStepIndex > additionalIndex) {
                        // Adjust current step index if we're past the additional step
                        this.currentStepIndex--;
                    }
                    this.stepOrder.splice(additionalIndex, 1);
                    // Hide the additional step content
                    const additionalContent = this.stepContents['additional'];
                    if (additionalContent) {
                        additionalContent.style.display = 'none';
                    }
                }
            }

            // Show/hide Additional step header
            const additionalStepHeader = document.getElementById('additional-step-header');
            const additionalLineAfter = document.getElementById('additional-line-after');
            if (additionalStepHeader) {
                additionalStepHeader.style.display = showAdditional ? 'block' : 'none';
            }
            if (additionalLineAfter) {
                additionalLineAfter.style.display = showAdditional ? 'block' : 'none';
            }

            // Update step visuals to reflect changes
            this.updateStepVisuals();
        }

        checkAndToggleAdditionalStep() {
            let typeId = null;
            
            // First, try to use currentPurchaseRequestTypeID if it's already set
            if (this.currentPurchaseRequestTypeID) {
                typeId = this.currentPurchaseRequestTypeID;
            } else {
                // Otherwise, get from PurchaseRequestType field (now contains ID, not string value)
                const purchReqTypeField = document.getElementById('PurchaseRequestType');
                if (!purchReqTypeField || !purchReqTypeField.value) {
                    this.toggleAdditionalStep(false);
                    return;
                }

                // PurchaseRequestType field now contains ID directly
                const purchReqTypeId = parseInt(purchReqTypeField.value.trim(), 10);
                if (isNaN(purchReqTypeId) || purchReqTypeId <= 0) {
                    this.toggleAdditionalStep(false);
                    return;
                }

                typeId = purchReqTypeId;
                this.currentPurchaseRequestTypeID = typeId;
            }

            // Get current Purchase Request Sub Type ID
            const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
            
            // For Type ID 5 or 7, Additional step can be shown without Sub Type (Subscribe section)
            if (typeId === 5 || typeId === 7) {
                const shouldShowAdditional = true;
                this.toggleAdditionalStep(shouldShowAdditional);
                this.updateAdditionalSectionVisibility(typeId, null);
                return;
            }
            
            // For other types, Sub Type is required
            if (!purchReqSubTypeField || !purchReqSubTypeField.value) {
                // If no sub type selected, hide Additional step
                this.toggleAdditionalStep(false);
                return;
            }

            // PurchaseRequestSubType field now contains ID directly
            const purchReqSubTypeId = parseInt(purchReqSubTypeField.value.trim(), 10);
            if (isNaN(purchReqSubTypeId) || purchReqSubTypeId <= 0) {
                this.toggleAdditionalStep(false);
                return;
            }

            const subTypeId = purchReqSubTypeId;
            this.currentPurchaseRequestSubTypeID = subTypeId;
            
            // Helper function to check if Sonumb Section should be shown
            const shouldShowSonumbSection = () => {
                // 1. Type ID = 8 && Sub Type ID = 4
                if (typeId === 8 && subTypeId === 4) return true;
                // 2. Type ID = 2 && (Sub Type ID = 1 || Sub Type ID = 3)
                if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
                // 3. Type ID = 4 && Sub Type ID = 3
                if (typeId === 4 && subTypeId === 3) return true;
                // 4. Type ID = 3 && (Sub Type ID = 4 || Sub Type ID = 5)
                if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
                return false;
            };
            
            // Show Additional step if:
            // 1. Purchase Request Type ID = 6 AND Purchase Request Sub Type ID = 2 (Billing Type section)
            // 2. Purchase Request Type ID = 5 OR 7 (Subscribe section, no Sub Type required)
            // 3. Sonumb Section conditions (Type ID 2,3,4,8 with specific Sub Type IDs)
            const shouldShowAdditional = (typeId === 6 && subTypeId === 2) || typeId === 5 || typeId === 7 || shouldShowSonumbSection();
            this.toggleAdditionalStep(shouldShowAdditional);
            
            // Update Additional section visibility based on Type ID and Sub Type ID
            this.updateAdditionalSectionVisibility(typeId, subTypeId);
        }
        
        updateAdditionalSectionVisibility(typeId, subTypeId) {
            const billingTypeSection = document.getElementById('billingTypeSection');
            const billingPeriodContainer = document.getElementById('billingPeriodContainer');
            const sonumbSection = document.getElementById('sonumbSection');
            const subscribeSection = document.getElementById('subscribeSection');
            const subscribePeriodContainer = document.getElementById('subscribePeriodContainer');
            const additionalDescription = document.getElementById('additionalDescription');
            
            // Hide all sections first
            if (billingTypeSection) billingTypeSection.style.display = 'none';
            if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
            if (sonumbSection) sonumbSection.style.display = 'none';
            if (subscribeSection) subscribeSection.style.display = 'none';
            if (subscribePeriodContainer) subscribePeriodContainer.style.display = 'none';
            
            // Helper function to check if Sonumb Section should be shown
            const shouldShowSonumbSection = () => {
                if (!subTypeId) return false;
                // 1. Type ID = 8 && Sub Type ID = 4
                if (typeId === 8 && subTypeId === 4) return true;
                // 2. Type ID = 2 && (Sub Type ID = 1 || Sub Type ID = 3)
                if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
                // 3. Type ID = 4 && Sub Type ID = 3
                if (typeId === 4 && subTypeId === 3) return true;
                // 4. Type ID = 3 && (Sub Type ID = 4 || Sub Type ID = 5)
                if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
                return false;
            };
            
            if (typeId === 6 && subTypeId === 2) {
                // Show Billing Type section
                if (billingTypeSection) billingTypeSection.style.display = 'block';
                
                // Check if billing type is already selected (for revision mode)
                const billingTypeHiddenInput = document.getElementById('BillingType');
                const billingTypeSelectedText = document.getElementById('billingTypeSelectedText');
                if (billingPeriodContainer && billingTypeHiddenInput && billingTypeHiddenInput.value) {
                    billingPeriodContainer.style.display = 'block';
                    // Update label if billing type text is available
                    if (billingTypeSelectedText && billingTypeSelectedText.textContent) {
                        const billingPeriodLabel = document.getElementById('billingPeriodLabel');
                        if (billingPeriodLabel) {
                            billingPeriodLabel.textContent = billingTypeSelectedText.textContent;
                        }
                    }
                    
                    // Ensure Period field is populated if it has a value (for revision mode)
                    const periodField = document.getElementById('Period');
                    if (periodField && periodField.value) {
                        // Period already has value, trigger calculation
                        setTimeout(() => {
                            if (typeof this.calculateEndPeriod === 'function') {
                                this.calculateEndPeriod();
                            }
                        }, 100);
                    }
                }
                
                if (sonumbSection) sonumbSection.style.display = 'none';
                if (subscribeSection) subscribeSection.style.display = 'none';
                if (additionalDescription) additionalDescription.textContent = 'Enter additional billing information.';
            } else if (typeId === 5 || typeId === 7) {
                // Show Subscribe section (Sonumb + Billing Type + Period with Year/Month/Date)
                if (billingTypeSection) billingTypeSection.style.display = 'none';
                if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
                if (sonumbSection) sonumbSection.style.display = 'none';
                if (subscribeSection) subscribeSection.style.display = 'block';
                if (additionalDescription) additionalDescription.textContent = 'Enter additional subscribe information.';
                
                // Re-initialize Subscribe Billing Type dropdown when section is shown
                // This ensures dropdown is populated if billing types were loaded after initial init
                if (this.billingTypes && this.billingTypes.length > 0) {
                    this.initializeSubscribeBillingTypeDropdown();
                }
                
                // Check if subscribe billing type is already selected (for revision mode)
                const subscribeBillingTypeHiddenInput = document.getElementById('SubscribeBillingType');
                const subscribeBillingTypeSelectedText = document.getElementById('subscribeBillingTypeSelectedText');
                if (subscribePeriodContainer && subscribeBillingTypeHiddenInput && subscribeBillingTypeHiddenInput.value) {
                    subscribePeriodContainer.style.display = 'block';
                    // Update label if billing type text is available
                    if (subscribeBillingTypeSelectedText && subscribeBillingTypeSelectedText.textContent) {
                        const subscribePeriodLabel = document.getElementById('subscribePeriodLabel');
                        if (subscribePeriodLabel) {
                            subscribePeriodLabel.textContent = subscribeBillingTypeSelectedText.textContent;
                        }
                    }
                    
                    // Ensure Subscribe Period field is populated if it has a value (for revision mode)
                    const subscribePeriodField = document.getElementById('SubscribePeriod');
                    if (subscribePeriodField && subscribePeriodField.value) {
                        // Period already has value, trigger calculation
                        setTimeout(() => {
                            if (typeof this.calculateSubscribeEndPeriod === 'function') {
                                this.calculateSubscribeEndPeriod();
                            }
                        }, 100);
                    }
                }
            } else if (shouldShowSonumbSection()) {
                // Show Sonumb section for specific Type ID and Sub Type ID combinations
                if (billingTypeSection) billingTypeSection.style.display = 'none';
                if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
                if (sonumbSection) sonumbSection.style.display = 'block';
                if (subscribeSection) subscribeSection.style.display = 'none';
                if (additionalDescription) additionalDescription.textContent = 'Enter additional site information.';
            } else {
                // Hide all sections
                if (billingTypeSection) billingTypeSection.style.display = 'none';
                if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
                if (sonumbSection) sonumbSection.style.display = 'none';
                if (subscribeSection) subscribeSection.style.display = 'none';
            }
            
            // Update quantity field based on period field visibility
            this.updateQuantityFieldBasedOnPeriod();
        }
        
        // Check if current Type ID/Sub Type ID shows period field (Billing Type or Subscribe section)
        hasPeriodField() {
            const typeId = this.currentPurchaseRequestTypeID;
            const subTypeId = this.currentPurchaseRequestSubTypeID;
            
            // Billing Type Section: Type ID 6 && Sub Type ID 2
            if (typeId === 6 && subTypeId === 2) {
                return true;
            }
            
            // Subscribe Section: Type ID 5 or 7
            if (typeId === 5 || typeId === 7) {
                return true;
            }
            
            return false;
        }
        
        // Update quantity field: disable and auto-fill from period field if period field is visible
        updateQuantityFieldBasedOnPeriod() {
            const quantityField = document.getElementById('quantity');
            if (!quantityField) return;
            
            const hasPeriod = this.hasPeriodField();
            
            if (hasPeriod) {
                // Disable quantity field
                quantityField.disabled = true;
                quantityField.setAttribute('readonly', 'readonly');
                
                // Get period value from appropriate field
                let periodValue = null;
                
                // Check Billing Type Section (Type ID 6, Sub Type ID 2)
                if (this.currentPurchaseRequestTypeID === 6 && this.currentPurchaseRequestSubTypeID === 2) {
                    const periodField = document.getElementById('Period');
                    if (periodField && periodField.value) {
                        periodValue = parseInt(periodField.value, 10);
                    }
                }
                // Check Subscribe Section (Type ID 5 or 7)
                else if (this.currentPurchaseRequestTypeID === 5 || this.currentPurchaseRequestTypeID === 7) {
                    const subscribePeriodField = document.getElementById('SubscribePeriod');
                    if (subscribePeriodField && subscribePeriodField.value) {
                        periodValue = parseInt(subscribePeriodField.value, 10);
                    }
                }
                
                // Auto-fill quantity with period value
                if (periodValue && !isNaN(periodValue) && periodValue > 0) {
                    quantityField.value = this.formatNumberWithComma(periodValue, 3);
                    console.log('Quantity auto-filled from period:', periodValue);
                    
                    // Remove validation error when quantity is auto-filled (even if disabled)
                    quantityField.classList.remove('is-invalid');
                    if (this.validationModule && this.validationModule.hideFieldError) {
                        this.validationModule.hideFieldError(quantityField);
                    }
                    
                    // Trigger amount calculation if unit price is already set
                    const unitPriceField = document.getElementById('unitPrice');
                    if (unitPriceField && unitPriceField.value) {
                        setTimeout(() => {
                            if (typeof this.initializeAmountCalculation === 'function') {
                                this.initializeAmountCalculation();
                            }
                        }, 100);
                    }
                } else {
                    // Clear quantity if period is not set
                    quantityField.value = '';
                }
            } else {
                // Enable quantity field
                quantityField.disabled = false;
                quantityField.removeAttribute('readonly');
            }
        }

        // Update quantity in all grid view items when Period changes
        updateQuantityInGridItems() {
            // Check if Additional section is visible and has Period field
            const hasPeriod = this.hasPeriodField();
            if (!hasPeriod) {
                return; // No period field, skip update
            }

            // Get period value from appropriate field
            let periodValue = null;
            
            // Check Billing Type Section (Type ID 6, Sub Type ID 2)
            if (this.currentPurchaseRequestTypeID === 6 && this.currentPurchaseRequestSubTypeID === 2) {
                const periodField = document.getElementById('Period');
                if (periodField && periodField.value) {
                    periodValue = parseInt(periodField.value, 10);
                }
            }
            // Check Subscribe Section (Type ID 5 or 7)
            else if (this.currentPurchaseRequestTypeID === 5 || this.currentPurchaseRequestTypeID === 7) {
                const subscribePeriodField = document.getElementById('SubscribePeriod');
                if (subscribePeriodField && subscribePeriodField.value) {
                    periodValue = parseInt(subscribePeriodField.value, 10);
                }
            }

            // If period value is not valid, skip update
            if (!periodValue || isNaN(periodValue) || periodValue <= 0) {
                return;
            }

            // Get all rows in the grid view
            const rows = document.querySelectorAll('#itemDetailTable tbody tr');
            if (rows.length === 0) {
                return; // No items in grid, skip update
            }

            let hasUpdated = false;

            // Update each row
            rows.forEach(row => {
                const itemDataAttr = row.getAttribute('data-item-data');
                if (!itemDataAttr) {
                    return; // Skip rows without data
                }

                try {
                    const itemData = JSON.parse(itemDataAttr);
                    
                    // Get current unit price (support both camelCase and PascalCase for compatibility)
                    const unitPrice = parseFloat(itemData.unitPrice || itemData.UnitPrice) || 0;
                    
                    // Update quantity with new period value
                    const newQuantity = periodValue;
                    itemData.itemQty = newQuantity;
                    // Also update ItemQty for compatibility
                    if (itemData.ItemQty !== undefined) {
                        itemData.ItemQty = newQuantity;
                    }
                    
                    // Recalculate amount
                    const newAmount = newQuantity * unitPrice;
                    itemData.amount = newAmount;
                    // Also update Amount for compatibility
                    if (itemData.Amount !== undefined) {
                        itemData.Amount = newAmount;
                    }
                    
                    // Update data attribute
                    row.setAttribute('data-item-data', JSON.stringify(itemData));
                    
                    // Update displayed quantity (column index 6, 0-based)
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 8) {
                        // Update Qty column (index 6)
                        cells[6].textContent = this.formatNumberWithComma(newQuantity, 3);
                        
                        // Update Amount column (index 8)
                        cells[8].textContent = this.formatNumberWithComma(newAmount, 2);
                    }
                    
                    hasUpdated = true;
                } catch (e) {
                    console.error('Error updating item quantity in grid:', e);
                }
            });

            // Update total amount if any items were updated
            if (hasUpdated) {
                this.updateAmountTotal();
                console.log('Updated quantities in grid view based on Period:', periodValue);
            }
        }

        // Helper function to focus on first invalid field
        focusFirstInvalidField(container) {
            if (!container) return;
            
            // First, check regular required fields
            const invalidFields = container.querySelectorAll('.is-invalid:not([disabled])');
            if (invalidFields.length > 0) {
                const firstField = invalidFields[0];
                // Scroll to field and focus
                firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // For input/textarea/select, focus directly
                if (firstField.tagName === 'INPUT' || firstField.tagName === 'TEXTAREA' || firstField.tagName === 'SELECT') {
                    setTimeout(() => firstField.focus(), 100);
                } else if (firstField.tagName === 'BUTTON') {
                    // For dropdown buttons, focus and optionally open dropdown
                    setTimeout(() => {
                        firstField.focus();
                        // Try to open dropdown if it's a Bootstrap dropdown
                        if (typeof bootstrap !== 'undefined') {
                            const dropdown = new bootstrap.Dropdown(firstField);
                            dropdown.show();
                        }
                    }, 100);
                }
                return true;
            }
            
            // Then check custom dropdowns (hidden inputs with dropdown buttons)
            // Check for Basic Information dropdowns
            const basicInfoDropdowns = [
                { inputId: '#Applicant', btnId: '#applicantDropdownBtn' },
                { inputId: '#Company', btnId: '#companyDropdownBtn' },
                { inputId: '#PurchaseRequestType', btnId: '#purchaseRequestTypeDropdownBtn' },
                { inputId: '#PurchaseRequestSubType', btnId: '#purchaseRequestSubTypeDropdownBtn' }
            ];
            
            for (const { inputId, btnId } of basicInfoDropdowns) {
                const hiddenInput = container.querySelector(inputId);
                const dropdownBtn = container.querySelector(btnId);
                if (hiddenInput && dropdownBtn && hiddenInput.hasAttribute('required')) {
                    const isEmpty = !hiddenInput.value || hiddenInput.value.trim() === '';
                    if (isEmpty && dropdownBtn.classList.contains('is-invalid')) {
                        dropdownBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => {
                            dropdownBtn.focus();
                            // Try to open dropdown if it's a Bootstrap dropdown
                            if (typeof bootstrap !== 'undefined') {
                                const dropdown = new bootstrap.Dropdown(dropdownBtn);
                                dropdown.show();
                            }
                        }, 100);
                        return true;
                    }
                }
            }
            
            // Check for Add Item Detail dropdowns
            const itemDetailDropdowns = [
                { inputId: '#unit', btnId: '#unitDropdownBtn' },
                { inputId: '#currency', btnId: '#currencyDropdownBtn' }
            ];
            
            for (const { inputId, btnId } of itemDetailDropdowns) {
                const hiddenInput = container.querySelector(inputId);
                const dropdownBtn = container.querySelector(btnId);
                if (hiddenInput && dropdownBtn && hiddenInput.hasAttribute('required')) {
                    const isEmpty = !hiddenInput.value || hiddenInput.value.trim() === '';
                    if (isEmpty && dropdownBtn.classList.contains('is-invalid')) {
                        dropdownBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => {
                            dropdownBtn.focus();
                            // Try to open dropdown if it's a Bootstrap dropdown
                            if (typeof bootstrap !== 'undefined') {
                                const dropdown = new bootstrap.Dropdown(dropdownBtn);
                                dropdown.show();
                            }
                        }, 100);
                        return true;
                    }
                }
            }
            
            return false;
        }

        validateBasicInformationStep() {
            if (this.basicInfoModule) {
                return this.basicInfoModule.validateBasicInformationStep();
            }
            console.warn('ProcurementWizardBasicInfo module not available');
            return false;
        }

        updateSummary() {
            if (!this.cachedElements.form) return;
            
            const formData = new FormData(this.cachedElements.form);
            // Get current logged-in user name from config or form field
            const currentUserFullName = (window.ProcurementConfig && window.ProcurementConfig.currentUserFullName) || 
                                       formData.get('Requestor')?.toString().trim() || 'User';
            
            // Get field values directly from DOM elements (important for disabled/readonly fields)
            // This ensures we get the correct values even if fields are disabled
            const applicantField = document.getElementById('Applicant');
            const companyField = document.getElementById('Company');
            const purchReqTypeField = document.getElementById('PurchaseRequestType');
            const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
            
            // Get selected option text for dropdowns (for display in summary)
            // Works with both custom dropdowns (hidden input + selectedText) and regular select elements
            const getSelectedOptionText = (fieldElement) => {
                if (!fieldElement) return '';
                
                // For custom dropdowns (hidden input), get text from selectedText element
                if (fieldElement.type === 'hidden') {
                    const fieldId = fieldElement.id;
                    let selectedTextId = '';
                    
                    if (fieldId === 'Applicant') {
                        selectedTextId = 'applicantSelectedText';
                    } else if (fieldId === 'Company') {
                        selectedTextId = 'companySelectedText';
                    } else if (fieldId === 'PurchaseRequestType') {
                        selectedTextId = 'purchaseRequestTypeSelectedText';
                    } else if (fieldId === 'PurchaseRequestSubType') {
                        selectedTextId = 'purchaseRequestSubTypeSelectedText';
                    }
                    
                    if (selectedTextId) {
                        const selectedTextElement = document.getElementById(selectedTextId);
                        if (selectedTextElement) {
                            return selectedTextElement.textContent || '';
                        }
                    }
                    return fieldElement.value || '';
                }
                
                // For regular select elements (if any still exist)
                if (fieldElement.tagName === 'SELECT') {
                    const selectedOption = fieldElement.options[fieldElement.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    return selectedOption.textContent || selectedOption.text || selectedOption.value;
                }
                }
                
                return '';
            };
            
            // Get data from Assign Approval form (separate form)
            const applicantApprovalField = document.getElementById('applicantApproval');
            const applicantApprovalValue = applicantApprovalField ? applicantApprovalField.value : '';
            const reviewedByField = document.getElementById('reviewedBy');
            const reviewedByValue = reviewedByField ? reviewedByField.value : '';
            const approvedByField = document.getElementById('approvedBy');
            const approvedByValue = approvedByField ? approvedByField.value : '';
            const confirmedByField = document.getElementById('confirmedBy');
            const confirmedByValue = confirmedByField ? confirmedByField.value : '';
            const requestorApprovalField = document.getElementById('requestor');
            const requestorApprovalValue = requestorApprovalField ? requestorApprovalField.value : currentUserFullName;
            
            // Update basic information summary
            // Use direct element access for disabled fields to ensure we get the correct values
            const summaryData = {
                'review-requestor': formData.get('Requestor') || currentUserFullName,
                'review-applicant': getSelectedOptionText(applicantField) || formData.get('Applicant') || '',
                'review-company': getSelectedOptionText(companyField) || formData.get('Company') || '',
                'review-purchase-request-type': getSelectedOptionText(purchReqTypeField) || formData.get('PurchaseRequestType') || '',
                'review-purchase-request-sub-type': getSelectedOptionText(purchReqSubTypeField) || formData.get('PurchaseRequestSubType') || '',
                'review-purchase-request-name': formData.get('PurchaseRequestName') || '-',
                'review-remarks': formData.get('Remarks') || '-',
                'review-amount-total': this.cachedElements.amountTotal?.value || '0',
                'review-approval-requestor': requestorApprovalValue || currentUserFullName,
                'review-approval-applicant': applicantApprovalValue || '',
                'review-reviewed-by': reviewedByValue || '',
                'review-approved-by': approvedByValue || '',
                'review-confirmed-by': confirmedByValue || ''
            };

            // Batch update summary elements
            Object.entries(summaryData).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    // Use .value for input/textarea elements, .textContent for other elements
                    if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                        element.value = value || '-';
                    } else {
                        element.textContent = value || '-';
                    }
                }
            });
            
            // Delegate to Summary module
            if (this.summaryModule) {
                this.summaryModule.updateSummaryStep();
            } else {
                // Module not available - log warning
                console.warn('ProcurementWizardSummary module not available');
            }
        }


        cancelCreatePR() {
            // Show confirmation modal per AC27
            this.showCancelConfirmationModal();
        }

        showCancelConfirmationModal() {
            const modalId = 'cancelPRConfirmationModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <div class="mb-3">
                                    <i class="icon-base bx bx-x-circle text-warning" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="mb-3">Are you sure you want to cancel the request?</h5>
                                <p class="text-muted mb-0">The entered data will be lost!</p>
                            </div>
                            <div class="modal-footer border-0 justify-content-center gap-2">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    No, Keep Editing
                                </button>
                                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                                    <i class="icon-base bx bx-x me-2"></i>Yes, Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            const confirmBtn = document.getElementById('confirmCancelBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    modal.hide();
                    // Clear all localStorage draft data
                    localStorage.removeItem('procurementDraft');
                    localStorage.removeItem('procurementDraftItemsSaved');
                    localStorage.removeItem('procurementDraftApprovalSaved');
                    localStorage.removeItem('procurementDraftDocumentsSaved');
                    // Redirect to list page
                    window.location.href = '/Procurement/PurchaseRequest/List';
                });
            }
            
            // Clean up modal when hidden
            const modalElement = document.getElementById(modalId);
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
            });
            
            modal.show();
        }

        showValidationMessage(message) {
            // Use SweetAlert2 modal centered on screen for validation messages
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    html: message,
                    icon: 'warning',
                    title: 'Validation Error',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#696cff',
                    allowOutsideClick: true,
                    allowEscapeKey: true,
                    customClass: {
                        popup: 'swal2-validation',
                        htmlContainer: 'swal2-html-container'
                    }
                });
            } else {
                // Fallback to createAlert if SweetAlert2 is not loaded
            this.removeExistingAlerts();
            this.createAlert('danger', `<span id="validation-message">${message}</span>`);
            }
        }

        showFileValidationModal(invalidFiles) {
            const modalId = 'fileValidationModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            // Build error list
            const errorList = invalidFiles.map(({ file, error }) => 
                `<li class="mb-2"><strong>${this.escapeHtml(file.name)}</strong><br><small class="text-muted">${this.escapeHtml(error)}</small></li>`
            ).join('');
            
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <div class="mb-3">
                                    <i class="icon-base bx bx-error-circle text-danger" style="font-size: 4rem;"></i>
                                </div>
                                <h5 class="mb-3">Document Validation Failed</h5>
                                <div class="text-start">
                                    <p class="text-muted mb-3">The following document(s) cannot be uploaded:</p>
                                    <ul class="list-unstyled mb-0">
                                        ${errorList}
                                    </ul>
                                    ${invalidFiles.some(f => f.error.includes('exceeds maximum size')) ? 
                                        '<p class="text-danger mt-3 mb-0"><small><strong>Note:</strong> Maximum file size is 2MB</small></p>' : ''}
                                    ${invalidFiles.some(f => f.error.includes('format is not supported')) ? 
                                        '<p class="text-danger mt-3 mb-0"><small><strong>Note:</strong> Allowed formats: Word, XLSX, PDF, JPG, PNG</small></p>' : ''}
                                </div>
                            </div>
                            <div class="modal-footer border-0 justify-content-center">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                                    <i class="icon-base bx bx-check me-2"></i>OK, I Understand
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            // Clean up modal when hidden
            const modalElement = document.getElementById(modalId);
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
            });
            
            modal.show();
        }

        showSuccessMessage() {
            this.removeExistingAlerts();
            this.createAlert('success', '<strong>Success!</strong> Your procurement request has been submitted successfully.');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        showSuccessModal(title, message, prNumber, onClose = null) {
            if (typeof Swal === 'undefined') {
                // Fallback to basic alert if SweetAlert2 is not loaded
                const fullMessage = prNumber ? `${message}\n\nPR Number: ${prNumber}` : message;
                window.alert(`${title}\n\n${fullMessage}`);
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
                return;
            }

            let htmlContent = `<div style="text-align: center;">${this.escapeHtml(message)}</div>`;
            if (prNumber) {
                htmlContent += `<div style="text-align: center; margin-top: 1rem;"><strong>PR Number: <span style="color: #696cff;">${this.escapeHtml(prNumber)}</span></strong></div>`;
            }

            Swal.fire({
                title: title,
                html: htmlContent,
                icon: 'success',
                confirmButtonColor: '#696cff',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: true,
                animation: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            }).then((result) => {
                    if (onClose && typeof onClose === 'function') {
                        onClose();
                    }
                });
        }

        async saveDraft() {
            if (this.saveModule && typeof this.saveModule.saveDraft === 'function') {
                return this.saveModule.saveDraft();
            }
            console.warn('ProcurementWizardSave module or saveDraft method not available');
        }

        async saveDraftBasicInfo(skipRedirect = false) {
            if (this.saveModule && typeof this.saveModule.saveDraftBasicInfo === 'function') {
                return this.saveModule.saveDraftBasicInfo(skipRedirect);
            }
            console.warn('ProcurementWizardSave module or saveDraftBasicInfo method not available');
            return false;
        }

        showDraftSavedMessage(prNumber) {
            if (this.saveModule && typeof this.saveModule.showDraftSavedMessage === 'function') {
                return this.saveModule.showDraftSavedMessage(prNumber);
            }
            console.warn('ProcurementWizardSave module or showDraftSavedMessage method not available');
        }

        async saveDraftDocuments() {
            if (this.saveModule && typeof this.saveModule.saveDraftDocuments === 'function') {
                return this.saveModule.saveDraftDocuments();
            }
            console.warn('ProcurementWizardSave module or saveDraftDocuments method not available');
        }

        async saveDraftApproval() {
            if (this.saveModule && typeof this.saveModule.saveDraftApproval === 'function') {
                return this.saveModule.saveDraftApproval();
            }
            console.warn('ProcurementWizardSave module or saveDraftApproval method not available');
        }


        async saveDraftItems() {
            if (this.saveModule && typeof this.saveModule.saveDraftItems === 'function') {
                return this.saveModule.saveDraftItems();
            }
            console.warn('ProcurementWizardSave module or saveDraftItems method not available');
        }


        // Helper function to save items only (without saving basic info first)
        async saveDraftItemsOnly(prNumber) {
            if (this.saveModule && typeof this.saveModule.saveDraftItemsOnly === 'function') {
                return this.saveModule.saveDraftItemsOnly(prNumber);
            }
            console.warn('ProcurementWizardSave module or saveDraftItemsOnly method not available');
            throw new Error('Save module not available');
        }


        // Helper function to save Additional data only
        async saveDraftAdditionalOnly(prNumber) {
            if (this.saveModule && typeof this.saveModule.saveDraftAdditionalOnly === 'function') {
                return this.saveModule.saveDraftAdditionalOnly(prNumber);
            }
            console.warn('ProcurementWizardSave module or saveDraftAdditionalOnly method not available');
        }


        // Save Additional data (with PR Number check)
        async saveDraftAdditional() {
            if (this.saveModule && typeof this.saveModule.saveDraftAdditional === 'function') {
                return this.saveModule.saveDraftAdditional();
            }
            console.warn('ProcurementWizardSave module or saveDraftAdditional method not available');
        }


        // Helper function to save documents only (using bulk save like items)
        async saveDraftDocumentsOnly(prNumber) {
            if (this.saveModule && typeof this.saveModule.saveDraftDocumentsOnly === 'function') {
                return this.saveModule.saveDraftDocumentsOnly(prNumber);
            }
            console.warn('ProcurementWizardSave module or saveDraftDocumentsOnly method not available');
            throw new Error('Save module not available');
        }


        // Helper function to save approval only (without saving basic info and items first)
        async saveDraftApprovalOnly(prNumber) {
            if (this.saveModule && typeof this.saveModule.saveDraftApprovalOnly === 'function') {
                return this.saveModule.saveDraftApprovalOnly(prNumber);
            }
            console.warn('ProcurementWizardSave module or saveDraftApprovalOnly method not available');
            throw new Error('Save module not available');
        }


        initializeSubmitConfirmation() {
            const confirmCheckbox = document.getElementById('confirmSubmission');
            const submitBtn = document.getElementById('submitPRBtn');
            
            if (!confirmCheckbox || !submitBtn) {
                console.warn('Confirmation checkbox or submit button not found');
                return;
            }
            
            // Remove existing event listener if any (to avoid duplicates)
            const existingHandler = confirmCheckbox._submitConfirmationHandler;
            if (existingHandler) {
                confirmCheckbox.removeEventListener('change', existingHandler);
            }
            
            // Create handler function
            const handler = (e) => {
                submitBtn.disabled = !e.target.checked;
            };
            
            // Store handler reference for potential removal later
            confirmCheckbox._submitConfirmationHandler = handler;
            
            // Set button disabled state based on checkbox initial state
            submitBtn.disabled = !confirmCheckbox.checked;
            
            // Add event listener to checkbox to enable/disable button
            confirmCheckbox.addEventListener('change', handler);
        }

        // Validate all wizard steps before submitting
        validateAllWizardSteps() {
            if (this.validationModule && typeof this.validationModule.validateAllWizardSteps === 'function') {
                return this.validationModule.validateAllWizardSteps();
            }
            console.warn('ProcurementWizardValidation module or validateAllWizardSteps method not available');
            return { isValid: false, message: 'Validation module not available', firstInvalidField: null };
        }


        // Submit PR: Save Basic Info + Items + Approval + Documents (same as saveDraftDocuments but with submit message)
        async submitPR() {
            if (this.saveModule && typeof this.saveModule.submitPR === 'function') {
                return this.saveModule.submitPR();
            }
            console.warn('ProcurementWizardSave module or submitPR method not available');
        }


        // Save all data: Basic Info + Items + Approval + Documents
        async saveDraftAll() {
            if (this.saveModule && typeof this.saveModule.saveDraftAll === 'function') {
                return this.saveModule.saveDraftAll();
            }
            console.warn('ProcurementWizardSave module or saveDraftAll method not available');
        }


        getItemsFromTable() {
            if (this.saveModule && typeof this.saveModule.getItemsFromTable === 'function') {
                return this.saveModule.getItemsFromTable();
            }
            console.warn('ProcurementWizardSave module or getItemsFromTable method not available');
            return [];
        }

        removeExistingAlerts() {
            const existingAlert = document.getElementById('validation-alert');
            if (existingAlert) existingAlert.remove();
        }

        createAlert(type, message, autoHide = null) {
            const alertDiv = document.createElement('div');
            alertDiv.id = 'validation-alert';
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            
            this.cachedElements.stepperContent.insertBefore(alertDiv, this.cachedElements.stepperContent.firstChild);
            
            if (autoHide) {
                setTimeout(() => {
                    if (alertDiv.parentNode) alertDiv.remove();
                }, autoHide);
            }
        }

        showAddItemForm(skipReset = false) {
            // Delegate to Items module
            if (this.itemsModule) {
                return this.itemsModule.showAddItemForm(skipReset);
            }
            // Module not available - log warning
            console.warn('ProcurementWizardItems module not available');
        }

        showChooseItemTable() {
            if (this.itemsModule && typeof this.itemsModule.showChooseItemTable === 'function') {
                return this.itemsModule.showChooseItemTable();
            }
            console.warn('ProcurementWizardItems module or showChooseItemTable method not available');
        }

        setupSearchInputListeners() {
            if (this.itemsModule && typeof this.itemsModule.setupSearchInputListeners === 'function') {
                return this.itemsModule.setupSearchInputListeners();
            }
            console.warn('ProcurementWizardItems module or setupSearchInputListeners method not available');
        }

        async searchItems() {
            if (this.itemsModule && typeof this.itemsModule.searchItems === 'function') {
                return this.itemsModule.searchItems();
            }
            console.warn('ProcurementWizardItems module or searchItems method not available');
        }

        async loadItemsFromApi(itemId = null, itemName = null, prodPool = null, coa = null) {
            if (this.apiModule && typeof this.apiModule.loadItemsFromApi === 'function') {
                return this.apiModule.loadItemsFromApi(itemId, itemName, prodPool, coa);
            }
            console.warn('ProcurementWizardAPI module or loadItemsFromApi method not available');
        }

        updateChooseItemPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
            if (this.itemsModule && typeof this.itemsModule.updateChooseItemPagination === 'function') {
                return this.itemsModule.updateChooseItemPagination(totalItems, currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or updateChooseItemPagination method not available');
        }

        updateDetailPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
            if (this.itemsModule && typeof this.itemsModule.updateDetailPagination === 'function') {
                return this.itemsModule.updateDetailPagination(totalItems, currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or updateDetailPagination method not available');
        }

        updateDetailTableVisibility(currentPage, rowsPerPage) {
            if (this.itemsModule && typeof this.itemsModule.updateDetailTableVisibility === 'function') {
                return this.itemsModule.updateDetailTableVisibility(currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or updateDetailTableVisibility method not available');
        }

        handleDetailPageChange(page, rowsPerPage) {
            if (this.itemsModule && typeof this.itemsModule.handleDetailPageChange === 'function') {
                return this.itemsModule.handleDetailPageChange(page, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or handleDetailPageChange method not available');
        }

        getTotalDetailItems() {
            if (this.itemsModule && typeof this.itemsModule.getTotalDetailItems === 'function') {
                return this.itemsModule.getTotalDetailItems();
            }
            console.warn('ProcurementWizardItems module or getTotalDetailItems method not available');
            return 0;
        }

        updateDocumentPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
            if (this.documentsModule && typeof this.documentsModule.updateDocumentPagination === 'function') {
                return this.documentsModule.updateDocumentPagination(totalItems, currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardDocuments module or updateDocumentPagination method not available');
        }

        updateDocumentTableVisibility(currentPage, rowsPerPage) {
            if (this.documentsModule && typeof this.documentsModule.updateDocumentTableVisibility === 'function') {
                return this.documentsModule.updateDocumentTableVisibility(currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardDocuments module or updateDocumentTableVisibility method not available');
        }

        handleDocumentPageChange(page, rowsPerPage) {
            if (this.documentsModule && typeof this.documentsModule.handleDocumentPageChange === 'function') {
                return this.documentsModule.handleDocumentPageChange(page, rowsPerPage);
            }
            console.warn('ProcurementWizardDocuments module or handleDocumentPageChange method not available');
        }

        getTotalDocumentItems() {
            if (this.documentsModule && typeof this.documentsModule.getTotalDocumentItems === 'function') {
                return this.documentsModule.getTotalDocumentItems();
            }
            console.warn('ProcurementWizardDocuments module or getTotalDocumentItems method not available');
            return 0;
        }

        handleChooseItemPageChange(page, rowsPerPage) {
            if (this.itemsModule && typeof this.itemsModule.handleChooseItemPageChange === 'function') {
                return this.itemsModule.handleChooseItemPageChange(page, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or handleChooseItemPageChange method not available');
        }

        getTotalChooseItems() {
            if (this.itemsModule && typeof this.itemsModule.getTotalChooseItems === 'function') {
                return this.itemsModule.getTotalChooseItems();
            }
            console.warn('ProcurementWizardItems module or getTotalChooseItems method not available');
            return 0;
        }

        updateChooseItemTableVisibility(currentPage, rowsPerPage) {
            if (this.itemsModule && typeof this.itemsModule.updateChooseItemTableVisibility === 'function') {
                return this.itemsModule.updateChooseItemTableVisibility(currentPage, rowsPerPage);
            }
            console.warn('ProcurementWizardItems module or updateChooseItemTableVisibility method not available');
        }

        async loadUnitsFromApi() {
            if (this.apiModule && typeof this.apiModule.loadUnitsFromApi === 'function') {
                return this.apiModule.loadUnitsFromApi();
            }
            console.warn('ProcurementWizardAPI module or loadUnitsFromApi method not available');
        }

        async loadCurrenciesFromApi() {
            if (this.apiModule && typeof this.apiModule.loadCurrenciesFromApi === 'function') {
                return this.apiModule.loadCurrenciesFromApi();
            }
            console.warn('ProcurementWizardAPI module or loadCurrenciesFromApi method not available');
        }


        async loadCompaniesFromApi() {
            if (this.apiModule) {
                return this.apiModule.loadCompaniesFromApi();
            }
            console.warn('ProcurementWizardAPI module not available');
        }

        async loadApplicantsFromApi() {
            if (this.apiModule) {
                return this.apiModule.loadApplicantsFromApi();
            }
            console.warn('ProcurementWizardAPI module not available');
        }

        async autoFillApplicantFromBasicInfo() {
            // Get selected applicant from Basic Information step
            const applicantHiddenInput = document.getElementById('Applicant');
            const applicantSelectedText = document.getElementById('applicantSelectedText');
            const applicantApprovalField = document.getElementById('applicantApproval');
            
            if (!applicantHiddenInput || !applicantApprovalField) return;
            
            // Get selected value (the applicant employee ID)
            const applicantEmployeeID = applicantHiddenInput.value;
            const applicantName = applicantSelectedText ? applicantSelectedText.textContent : '';
            
            if (applicantEmployeeID) {
                // Set the display name in the field (for readability)
                applicantApprovalField.value = applicantName || applicantEmployeeID;
                
                // Also store employee ID in data attribute for future use if needed
                applicantApprovalField.setAttribute('data-employee-id', applicantEmployeeID);
            } else {
                applicantApprovalField.value = '';
                applicantApprovalField.removeAttribute('data-employee-id');
            }
        }

        async loadPurchaseTypesFromApi() {
            if (this.apiModule) {
                return this.apiModule.loadPurchaseTypesFromApi();
            }
            console.warn('ProcurementWizardAPI module not available');
        }

        async loadPurchaseSubTypesFromApi(mstPROPurchaseTypeID = null) {
            if (this.apiModule) {
                return this.apiModule.loadPurchaseSubTypesFromApi(mstPROPurchaseTypeID);
            }
            console.warn('ProcurementWizardAPI module not available');
        }



        // Generic function to render dropdown with search
        renderDropdownWithSearch(config) {
            // Delegate to Utils module
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.renderDropdownWithSearch(config);
            }
            console.warn('ProcurementWizardUtils module not available');
        }

        renderUnitDropdown(units, searchTerm = '') {
            if (this.itemsModule) {
                return this.itemsModule.renderUnitDropdown(units, searchTerm);
            }
            console.warn('ProcurementWizardItems module not available');
        }

        showDetailTable() {
            if (this.itemsModule) {
                return this.itemsModule.showDetailTable();
            }
            console.warn('ProcurementWizardItems module not available');
        }

        toggleDetailElements(show) {
            if (this.itemsModule) {
                return this.itemsModule.toggleDetailElements(show);
            }
            console.warn('ProcurementWizardItems module not available');
        }

        toggleFormElements(showAddForm, showChooseTable) {
            if (this.itemsModule) {
                return this.itemsModule.toggleFormElements(showAddForm, showChooseTable);
            }
            console.warn('ProcurementWizardItems module not available');
        }

        initializeAmountCalculation() {
            if (this.itemsModule) {
                return this.itemsModule.initializeAmountCalculation();
            }
            console.warn('ProcurementWizardItems module not available');
        }

        async addNewItemToTable(buttonElement = null) {
            if (this.itemsModule) {
                return this.itemsModule.addNewItemToTable(buttonElement);
            }
            console.warn('ProcurementWizardItems module not available');
        }

        createTableRow(formData) {
            if (this.itemsModule) {
                return this.itemsModule.createTableRow(formData);
            }
            console.warn('ProcurementWizardItems module not available');
            return null;
        }

        createTableRowFromItem(item) {
            if (this.itemsModule) {
                return this.itemsModule.createTableRowFromItem(item);
            }
            console.warn('ProcurementWizardItems module not available');
            return null;
        }

        // Helper function to format number with comma as thousand separator
        formatNumberWithComma(value, maxDecimals = 2) {
            // Use utility module if available, otherwise fallback to original implementation
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.formatNumberWithComma(value, maxDecimals);
            }
            // Fallback for backward compatibility
            if (isNaN(value) || value === null || value === undefined) return '';
            const num = parseFloat(value);
            if (isNaN(num)) return '';
            
            // Format with comma as thousand separator
            const parts = num.toString().split('.');
            let integerPart = parts[0];
            const decimalPart = parts[1] || '';
            
            // Add thousand separator (comma)
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Handle decimal part
            if (maxDecimals > 0 && decimalPart) {
                const limitedDecimal = decimalPart.substring(0, maxDecimals);
                return integerPart + '.' + limitedDecimal;
            } else if (maxDecimals > 0 && num % 1 !== 0) {
                // If number has decimal but decimal part is empty, format with maxDecimals
                return integerPart + '.' + num.toFixed(maxDecimals).split('.')[1];
            }
            
            return integerPart;
        }

        updateAmountTotal() {
            if (this.itemsModule) {
                return this.itemsModule.updateAmountTotal();
            }
            console.warn('ProcurementWizardItems module not available');
        }

        openFileUploadDialog() {
            // Delegate to Documents module
            if (this.documentsModule) {
                return this.documentsModule.openFileUploadDialog();
            }
            // Module not available - log warning
            console.warn('ProcurementWizardDocuments module not available');
        }

        validateAndAddDocuments(files) {
            if (this.documentsModule) {
                return this.documentsModule.validateAndAddDocuments(files);
            }
            console.warn('ProcurementWizardDocuments module not available');
        }

        addDocumentsToTable(files) {
            if (this.documentsModule) {
                return this.documentsModule.addDocumentsToTable(files);
            }
            console.warn('ProcurementWizardDocuments module not available');
        }

        logPerformanceMetrics() {
            // Performance logging - kept in main class as it's instance-specific
            const currentTime = performance.now();
            const totalTime = currentTime - this.performanceMetrics.startTime;
            
            if (this.performanceMetrics.stepTransitions % 3 === 0) {
                // Log performance metrics if needed
            }
        }

        // Cleanup method for memory optimization
        destroy() {
            // Cleanup - kept in main class as it's instance-specific
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Remove event listeners
            document.removeEventListener('click', this.handleClick.bind(this));
            
            // Clear cached elements
            this.cachedElements = null;
            this.stepContents = null;
        }

        async openUserSearchModal(searchTerm = null, targetFieldId = null) {
            if (this.userSearchModule) {
                return this.userSearchModule.openUserSearchModal(searchTerm, targetFieldId);
            }
            console.warn('ProcurementWizardUserSearch module not available');
        }

        async loadEmployeesToModal(searchTerm = null, targetFieldId = null, tableBodyId = 'employeeTableBody', paginationId = null, pageInfoId = null, uniqueId = null) {
            if (this.userSearchModule) {
                return this.userSearchModule.loadEmployeesToModal(searchTerm, targetFieldId, tableBodyId, paginationId, pageInfoId, uniqueId);
            }
            console.warn('ProcurementWizardUserSearch module not available');
        }

        renderEmployeePagination(paginationId, currentPage, totalPages, uniqueId, tableBodyId, targetFieldId, pageInfoId) {
            if (this.userSearchModule) {
                return this.userSearchModule.renderEmployeePagination(paginationId, currentPage, totalPages, uniqueId, tableBodyId, targetFieldId, pageInfoId);
            }
            console.warn('ProcurementWizardUserSearch module not available');
        }
    }

    // Note: Global functions (editRow, deleteRow, deleteDocument, selectUser, selectItem, cancelCreatePR),
    // CSS for loading spinner, and DOMContentLoaded event listener are now in ProcurementWizardGlobalFunctions.js

</script>
