// Use window object to avoid redeclaration conflict with apiHelper.js
if (typeof window.normalizedApiUrls === 'undefined') {
    window.normalizedApiUrls = {};
}

function CreateTable({
    apiType,
    endpoint,
    tableId,
    columns,
    filter = null,
    order = null,
    excelCall = null,
    footerCallback = null,
    fnDrawCallback = null,
    fnPreDrawCallback = null,
    columnDefs = null,
    serverSide = true,
    data = [],
    leftColumns = 2
}) {
    // Validate that configuration from server is available
    if (typeof API_URLS === 'undefined') {
        throw new Error('API_URLS configuration not found. Please ensure it is rendered from Razor View.');
    }

    // Normalize API_URLS every time function is called (like in apiCall)
    // This ensures API_URLS is available when normalization is performed
    if (typeof API_URLS !== 'undefined') {
        for (const key in API_URLS) {
            if (API_URLS.hasOwnProperty(key)) {
                window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
            }
        }
    }

    apiType = apiType.toLowerCase();
    const baseUrl = window.normalizedApiUrls[apiType];
    if (!baseUrl) {
        // Debug: log available keys for troubleshooting
        const availableKeys = Object.keys(window.normalizedApiUrls).join(', ');
        throw new Error(`Invalid API type: ${apiType}. Available keys: ${availableKeys}`);
    }

    const fullUrl = baseUrl + endpoint;

    const mappedColumns = columns.map(col => {
        if (typeof col === 'string') {
            return { data: col };
        }

        // Return all column properties, not just data and render
        const mappedCol = {
            data: col.data || null, // Ensure data is always defined (can be null for non-data columns)
            render: col.render || undefined
        };
        
        // Include other DataTables column properties if present
        if (col.title !== undefined) mappedCol.title = col.title;
        if (col.orderable !== undefined) mappedCol.orderable = col.orderable;
        if (col.searchable !== undefined) mappedCol.searchable = col.searchable;
        if (col.width !== undefined) mappedCol.width = col.width;
        if (col.className !== undefined) mappedCol.className = col.className;
        
        return mappedCol;
    });

    const table = $(`${tableId}`).DataTable({
        scrollX: true,
        autoWidth: false,
        processing: true,
        deferRender: true,
        scrollY: 400,
        scrollCollapse: true,
        orderCellsTop: true,
        serverSide: serverSide,
        paging: true,
        lengthMenu: [[10, 50, 100], ['10', '50', '100']],
        destroy: true,
        language: {
            "emptyTable": "No data available in table",
        },
        fixedColumns: {
            leftColumns: leftColumns
        },
        order: order || [],
        drawCallback: function(settings) {
            const self = this;
            // Recalculate layout after each draw to fix column width issues
            // This fixes the issue where header columns are cut off after refresh
            setTimeout(function() {
                try {
                    if (self.api().columns && self.api().columns.adjust) {
                        self.api().columns.adjust();
                    }
                    // Force fixed columns recalculation if enabled
                    // Use settings parameter or api().settings() as fallback
                    try {
                        let tableSettings = null;
                        if (settings && settings[0]) {
                            tableSettings = settings[0];
                        } else if (self.api && typeof self.api === 'function') {
                            const api = self.api();
                            if (api && api.settings && typeof api.settings === 'function') {
                                const apiSettings = api.settings();
                                if (apiSettings && apiSettings[0]) {
                                    tableSettings = apiSettings[0];
                                }
                            }
                        }
                        if (tableSettings && tableSettings.oFeatures && tableSettings.oFeatures.bFixedColumns) {
                            if (self.fixedColumns && typeof self.fixedColumns === 'function') {
                                self.fixedColumns().relayout();
                            }
                        }
                    } catch (e) {
                        // Fixed columns extension might not be available or settings not accessible
                        // Silently ignore
                    }
                    // Force scroll container width recalculation
                    const wrapper = $(self.table().node()).closest('.dataTables_wrapper');
                    if (wrapper.length) {
                        const scrollHead = wrapper.find('.dataTables_scrollHead');
                        const scrollBody = wrapper.find('.dataTables_scrollBody');
                        if (scrollHead.length && scrollBody.length) {
                            // Ensure scroll head width matches scroll body width
                            const bodyWidth = scrollBody[0].scrollWidth;
                            if (bodyWidth > 0) {
                                scrollHead.css('width', bodyWidth + 'px');
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Error adjusting columns in drawCallback:', e);
                }
            }, 50);
            
            // Additional recalculation after a longer delay
            setTimeout(function() {
                try {
                    if (self.api().columns && self.api().columns.adjust) {
                        self.api().columns.adjust();
                    }
                    // Use settings parameter or api().settings() as fallback
                    try {
                        let tableSettings = null;
                        if (settings && settings[0]) {
                            tableSettings = settings[0];
                        } else if (self.api && typeof self.api === 'function') {
                            const api = self.api();
                            if (api && api.settings && typeof api.settings === 'function') {
                                const apiSettings = api.settings();
                                if (apiSettings && apiSettings[0]) {
                                    tableSettings = apiSettings[0];
                                }
                            }
                        }
                        if (tableSettings && tableSettings.oFeatures && tableSettings.oFeatures.bFixedColumns) {
                            if (self.fixedColumns && typeof self.fixedColumns === 'function') {
                                self.fixedColumns().relayout();
                            }
                        }
                    } catch (e) {
                        // Ignore error
                    }
                } catch (e) {
                    // Ignore error
                }
            }, 200);
            
            // Call custom draw callback if provided
            if (typeof fnDrawCallback === 'function') {
                try {
                    fnDrawCallback.call(this, settings);
                } catch (e) {
                    console.warn('Error in custom drawCallback:', e);
                }
            }
        },
        initComplete: function(settings, json) {
            const self = this;
            // Recalculate layout after initialization
            setTimeout(function() {
                try {
                    if (self.api().columns && self.api().columns.adjust) {
                        self.api().columns.adjust();
                    }
                    // Force fixed columns recalculation if enabled
                    // Use settings parameter or api().settings() as fallback
                    try {
                        let tableSettings = null;
                        if (settings && settings[0]) {
                            tableSettings = settings[0];
                        } else if (self.api && typeof self.api === 'function') {
                            const api = self.api();
                            if (api && api.settings && typeof api.settings === 'function') {
                                const apiSettings = api.settings();
                                if (apiSettings && apiSettings[0]) {
                                    tableSettings = apiSettings[0];
                                }
                            }
                        }
                        if (tableSettings && tableSettings.oFeatures && tableSettings.oFeatures.bFixedColumns) {
                            if (self.fixedColumns && typeof self.fixedColumns === 'function') {
                                self.fixedColumns().relayout();
                            }
                        }
                    } catch (e) {
                        // Fixed columns extension might not be available or settings not accessible
                        // Silently ignore
                    }
                } catch (e) {
                    console.warn('Error adjusting columns in initComplete:', e);
                }
            }, 200);
        },
        // DataTables Buttons extension - Excel only
        buttons: [
            'excel'
        ],
        ajax: serverSide ? {
            url: fullUrl,
            type: 'POST',
            contentType: 'application/json',
            xhrFields: {
                withCredentials: true 
            },
            headers: (function () {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                return tokenMeta ? { 'X-CSRF-TOKEN': tokenMeta.getAttribute('content') } : {};
            })(),
            data: function (d) {
                const filterData = (typeof filter === 'function') ? filter() : {};
                return JSON.stringify({
                    ...d,
                    ...filterData
                });
            },
            dataSrc: 'data',
            dataFilter: function (rawData) {
                const json = JSON.parse(rawData);

                // Handle direct DataTablesResponse format (from GetGrid endpoint)
                if (json && typeof json === 'object' && 'draw' in json && 'recordsTotal' in json && 'recordsFiltered' in json && 'data' in json) {
                    return JSON.stringify({
                        draw: json.draw ?? 0,
                        recordsTotal: json.recordsTotal ?? 0,
                        recordsFiltered: json.recordsFiltered ?? 0,
                        data: json.data || []
                    });
                }

                // Handle ApiResponse wrapper format: { statusCode, data: { draw, recordsTotal, recordsFiltered, data } }
                if (Array.isArray(json?.data)) {
                    return JSON.stringify({
                        draw: 0,
                        recordsTotal: json.data.length,
                        recordsFiltered: json.data.length,
                        data: json.data
                    });
                }

                const dtData = json?.data;

                // Handle ApiResponse wrapper with DataTablesResponse inside
                if (dtData && typeof dtData === 'object' && 'draw' in dtData && 'recordsTotal' in dtData && 'recordsFiltered' in dtData && 'data' in dtData) {
                    return JSON.stringify({
                        draw: dtData.draw ?? 0,
                        recordsTotal: dtData.recordsTotal ?? 0,
                        recordsFiltered: dtData.recordsFiltered ?? 0,
                        data: dtData.data || []
                    });
                }

                // Handle legacy array format
                if (dtData && Array.isArray(dtData.data)) {
                    return JSON.stringify({
                        draw: dtData.draw ?? 0,
                        recordsTotal: dtData.recordsTotal ?? 0,
                        recordsFiltered: dtData.recordsFiltered ?? 0,
                        data: dtData.data
                    });
                }

                return JSON.stringify({
                    draw: 0,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: []
                });
            }
        } : undefined,
        data: serverSide ? undefined : data,

        columns: mappedColumns,
        columnDefs: columnDefs || [],
        dom: "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12 text-end'f>>" +
            "r<'table-scrollable't>" +
            "<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12 text-end'p>>",
        footerCallback: footerCallback || undefined,
        fnPreDrawCallback: fnPreDrawCallback || undefined
    });

    window._activeDataTables = window._activeDataTables || [];
    window._activeDataTables = window._activeDataTables.filter(dt => {
        return dt.table().node() !== $(tableId)[0];
    });
    window._activeDataTables.push(table);
    if (!window._resizeHandlerBound) {
        $(window).on('resize', function () {
            window._activeDataTables.forEach(dt => {
                if ($.fn.dataTable.isDataTable(dt.table().node())) {
                    try {
                        dt.columns.adjust();
                        // Force fixed columns recalculation
                        if (dt.settings()[0].oFeatures.bFixedColumns) {
                            dt.fixedColumns().relayout();
                        }
                    } catch (e) {
                        console.warn('Error adjusting columns:', e);
                    }
                }
            });
        });
        window._resizeHandlerBound = true;
    }
    
    // Force recalculation after table is fully rendered
    // Multiple timeouts to ensure layout is recalculated at different stages
    setTimeout(function() {
        if (table && table.columns && table.columns.adjust) {
            try {
                table.columns.adjust();
                // Force fixed columns recalculation
                if (table.settings()[0].oFeatures.bFixedColumns) {
                    try {
                        table.fixedColumns().relayout();
                    } catch (e) {
                        console.warn('Error relaying fixed columns:', e);
                    }
                }
            } catch (e) {
                console.warn('Error adjusting columns:', e);
            }
        }
    }, 300);
    
    // Additional recalculation after a longer delay to catch any late rendering
    setTimeout(function() {
        if (table && table.columns && table.columns.adjust) {
            try {
                table.columns.adjust();
                // Force fixed columns recalculation again
                if (table.settings()[0].oFeatures.bFixedColumns) {
                    try {
                        table.fixedColumns().relayout();
                    } catch (e) {
                        // Ignore error
                    }
                }
            } catch (e) {
                // Ignore error
            }
        }
    }, 600);
    
    // Listen for visibility change (when tab becomes visible again)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && table && table.columns && table.columns.adjust) {
            setTimeout(function() {
                try {
                    table.columns.adjust();
                    if (table.settings()[0].oFeatures.bFixedColumns) {
                        try {
                            table.fixedColumns().relayout();
                        } catch (e) {
                            // Ignore error
                        }
                    }
                } catch (e) {
                    // Ignore error
                }
            }, 100);
        }
    });
    let searchDelayTimer;
    $(`${tableId}_filter input`)
        .off()
        .on('keyup', function () {
            const query = this.value;
            clearTimeout(searchDelayTimer);
            searchDelayTimer = setTimeout(function () {
                table.search(query).draw();
            }, 500);
        });

    return table;
}

// Helper function to export DataTable to Excel
function exportExcel(tableId) {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
        console.error('jQuery or DataTables not loaded');
        return;
    }
    
    const table = $(tableId).DataTable();
    if (!table) {
        console.error('DataTable not found for:', tableId);
        return;
    }
    
    // Trigger Excel export button
    try {
        table.button('.buttons-excel').trigger();
    } catch (e) {
        console.error('Error exporting to Excel:', e);
        // Fallback: try to find and click the excel button
        const excelButton = $(tableId + '_wrapper').find('.buttons-excel');
        if (excelButton.length) {
            excelButton.click();
        } else {
            alert('Excel export button not found. Please ensure DataTables Buttons extension is loaded.');
        }
    }
}

// Helper functions for formatting (number only, no currency symbol)
function formatCurrency(amount) {
    if (!amount && amount !== 0) return '-';
    const num = parseFloat(amount);
    if (isNaN(num)) return '-';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
}

function formatDateTime(date) {
    if (!date) return '-';
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(dateObj);
}

function ColumnBuilder(cols) {
    return cols.map(col => {
        if (typeof col === 'string') {
            return { data: col };
        }

        const { data, render, type } = col;

        if (render) return { data, render };

        switch (type) {
            case 'currency':
                return {
                    data,
                    render: d => formatCurrency(d)
                };
            case 'active':
                return {
                    data,
                    render: d => d == true ? "Active" : "InActive"
                };
            case 'datetime':
                return {
                    data,
                    render: d => formatDateTime(d)
                };

            case 'actions':
                return {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        const buttons = col.buttons.map(btn => {
                            const visible = btn.showIf ? btn.showIf(row) : true;
                            if (!visible) return '';

                            const iconHtml = btn.icon || btn.label;
                            const title = btn.title || btn.label;

                            return `<button 
                                        class="btn btn-sm ${btn.className || 'btn-secondary'} action-btn" 
                                        data-action="${btn.label.toLowerCase()}" 
                                        data-id="${row.id}" 
                                        title="${title}" 
                                        aria-label="${title}">
                                        ${iconHtml}
                                    </button>`;
                        }).filter(Boolean);

                        return `<div class="d-flex justify-content-center gap-2">${buttons.join('')}</div>`;
                    }

                };

            default:
                return { data };
        }
    });
}

