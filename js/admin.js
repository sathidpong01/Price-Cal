document.addEventListener('DOMContentLoaded', function() {

    // --- 1. Global Variables & State ---
    const paginatedTables = {}; // Object to hold state for each paginated table

    // --- 2. Helper Functions ---
    function formatNumber(num) {
        try {
            let parsedNum = parseFloat(num);
            if (isNaN(parsedNum)) return '0.00';
            return parsedNum.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } catch (e) { return '0.00'; }
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }
    
    // --- 3. Sidebar Navigation ---
    const sidebarLinks = document.querySelectorAll('.sidebar-menu .sidebar-link');
    const contentPanels = document.querySelectorAll('.main-admin-content .admin-section');

    function showContentPanel(panelIdToShow) {
        contentPanels.forEach(panel => {
            panel.style.display = (panel.id === panelIdToShow) ? 'block' : 'none';
        });
    }

    function setActiveLink(linkToActivate) {
        sidebarLinks.forEach(link => link.classList.remove('active'));
        if (linkToActivate) linkToActivate.classList.add('active');
    }

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const targetPanelId = this.getAttribute('href').substring(1);
            if (document.getElementById(targetPanelId)) {
                showContentPanel(targetPanelId);
                setActiveLink(this);
            }
        });
    });

    function activateSectionFromHash() {
        const hash = window.location.hash || '#rules_section'; // Default to #rules_section if no hash
        const link = document.querySelector(`.sidebar-link[href="${hash}"]`);
        if (link) {
            link.click();
            return true;
        }
        return false;
    }

    // --- 4. Modal and Form Handling ---
    window.openEditModal = function(type, id) {
        const modalId = `edit${type.charAt(0).toUpperCase() + type.slice(1)}Modal`;
        const formId = `edit${type.charAt(0).toUpperCase() + type.slice(1)}Form`;
        const url = `admin_ajax_data_handler.php?action=get_${type}_data&id=${id}`;

        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        if (!modal || !form) return;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    form.reset(); // Reset form before populating
                    const itemData = data.data;
                    form.elements[`${type}_id`].value = id;
                    
                    // A more generic way to populate form fields
                    for (const key in itemData) {
                        if (form.elements[key]) {
                            form.elements[key].value = itemData[key];
                        } else if (form.elements[`edit_${type}_${key}`]) {
                            form.elements[`edit_${type}_${key}`].value = itemData[key];
                        }
                    }
                    // Handle specific cases where form name differs from db column
                    if (type === 'material') form.elements['material_price'].value = itemData['price_per_unit'];
                    if (type === 'option') form.elements['option_category'].value = itemData['category'];

                    modal.style.display = "block";
                    form.querySelector('input, select')?.focus();
                } else {
                    displayGlobalMessage('error', 'ไม่สามารถดึงข้อมูลได้: ' + (data.error || 'ไม่ทราบสาเหตุ'));
                }
            })
            .catch(error => displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message));
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    };

    function submitModalForm(formElement) {
        const formData = new FormData(formElement);
        const modalId = formElement.closest('.modal').id;
        const itemType = formData.get('action').replace('update_', '');

        fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal(modalId);
                    displayGlobalMessage('success', data.message || 'บันทึกข้อมูลเรียบร้อย!');
                    if (data.data) {
                        updateTableRow(itemType, data.data);
                    }
                } else {
                    displayGlobalMessage('error', 'เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถบันทึกข้อมูลได้'), modalId);
                }
            });
    }

    document.querySelectorAll('.modal form').forEach(form => {
        form.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this); });
    });

    // --- 5. Dynamic Table Row Update & Deletion ---
    function updateTableRow(type, data) {
        const idField = `${type}_id`;
        const rowId = `${type}-row-${data[idField]}`;
        const row = document.getElementById(rowId);
        if (!row) return;

        // Update data-fields
        row.querySelectorAll('td[data-field]').forEach(cell => {
            const field = cell.getAttribute('data-field');
            // Handle name mapping differences
            let dataKey = field;
            if (type === 'material' && field === 'price') dataKey = 'price_per_unit';
            if (type === 'option' && field === 'category') dataKey = 'option_category';

            let newValue = data[dataKey] || '';
            
            if (['value', 'price', 'price_per_unit', 'option_price'].includes(field)) {
                 newValue = formatNumber(newValue);
            }
            
            cell.textContent = htmlspecialchars(String(newValue));
        });
        
        row.style.backgroundColor = '#d4edda';
        setTimeout(() => { row.style.backgroundColor = ''; }, 2000);
    }

    window.handleDeleteClick = function(type, id, name) {
        if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ '${name}'? การกระทำนี้ไม่สามารถย้อนกลับได้`)) {
            const formData = new FormData();
            formData.append('action', `delete_${type}`);
            formData.append('id', id);

            fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayGlobalMessage('success', data.message);
                        const row = document.getElementById(`${type}-row-${id}`);
                        if (row) {
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                // Re-filter and re-paginate after deletion
                                applyCombinedFilters(); 
                            }, 500);
                        }
                    } else {
                        displayGlobalMessage('error', data.error);
                    }
                });
        }
    };
    
    // --- 6. Stock Specific Logic ---
    window.handleStockUpdate = function(stockId, buttonElement) {
        const row = buttonElement.closest('tr');
        const input = row.querySelector('.stock-quantity-input');
        const newQuantity = input.value;

        const formData = new FormData();
        formData.append('action', 'update_stock_quantity');
        formData.append('stock_id', stockId);
        formData.append('quantity', newQuantity);

        fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGlobalMessage('success', data.message || 'อัปเดตสต็อกเรียบร้อย!');
                    input.setAttribute('data-initial-value', newQuantity);
                    row.classList.remove('changed');
                    buttonElement.style.visibility = 'hidden';
                    buttonElement.style.opacity = '0';
                } else {
                    displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาด');
                }
            });
    };

    function setupStockControls() {
        document.querySelectorAll('.stock-quantity-input').forEach(input => {
            const row = input.closest('tr');
            const saveButton = row.querySelector('.btn-stock-save');
            if(row && saveButton){
                input.addEventListener('input', () => {
                    const isChanged = input.value !== input.getAttribute('data-initial-value');
                    row.classList.toggle('changed', isChanged);
                    saveButton.style.visibility = isChanged ? 'visible' : 'hidden';
                    saveButton.style.opacity = isChanged ? '1' : '0';
                });
            }
        });
    }

    // --- 7. Display Toggles ---
    document.querySelectorAll('.rule-display-toggle, .material-display-toggle, .option-display-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const type = this.className.split(' ')[1].replace('-display-toggle', '');
            const id = this.dataset[`${type}Id`];
            const visible = this.checked ? 1 : 0;
            
            fetch('admin_ajax_data_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_display_status&type=${type}&id=${id}&visible=${visible}`
            }).then(res => res.json()).then(data => {
                if(!data.success) {
                    displayGlobalMessage('error', data.error || 'Update failed');
                    this.checked = !this.checked; // Revert on failure
                }
            });
        });
    });

    // --- 8. Pagination & Filtering Logic ---
    const tableSearchConfigs = {
        'rulesTable': { individualInputId: 'ruleSearch', searchCols: [1, 3] },
        'materialsTable': { individualInputId: 'materialSearch', searchCols: [1, 2, 4] },
        'optionsTable': { individualInputId: 'optionSearch', searchCols: [1, 3] },
        'stockManagementTable': {
            individualInputId: 'stockSearch',
            searchCols: [2, 4],
            typeFilterDropdownId: 'stockTypeFilter',
            typeColumnIndex: 1
        }
    };

    function initPagination(tableId, rowsPerPage) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        paginatedTables[tableId] = {
            rowsPerPage: rowsPerPage,
            currentPage: 1,
            originalRows: Array.from(tbody.querySelectorAll('tr')),
            filteredRows: Array.from(tbody.querySelectorAll('tr'))
        };
    }

    function showPage(tableId, page) {
        const state = paginatedTables[tableId];
        if (!state) return;

        const rows = state.filteredRows;
        const totalRows = rows.length;
        const rowsPerPage = state.rowsPerPage;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
        state.currentPage = Math.max(1, Math.min(page, totalPages));

        state.originalRows.forEach(row => row.style.display = 'none');
        const startIndex = (state.currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        rows.slice(startIndex, endIndex).forEach(row => row.style.display = '');
        
        updatePaginationControls(tableId);
        updateNoDataRowVisibility(tableId);
    }

    function updatePaginationControls(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const state = paginatedTables[tableId];
        if (!state) return;
        
        const totalPages = Math.ceil(state.filteredRows.length / state.rowsPerPage) || 1;
        
        let pagDiv = table.closest('.table-responsive').querySelector('.pagination');
        if (!pagDiv) {
            pagDiv = document.createElement('div');
            pagDiv.className = 'pagination';
            table.closest('.table-responsive').appendChild(pagDiv);
        }
        pagDiv.innerHTML = '';
        if (totalPages <= 1) { pagDiv.style.display = 'none'; return; }
        
        pagDiv.style.display = 'flex';
        
        const createButton = (text, pageNum, isActive = false, isDisabled = false) => {
            const btn = document.createElement('button');
            btn.className = 'pagination-btn' + (isActive ? ' active' : '');
            btn.textContent = text;
            btn.onclick = () => showPage(tableId, pageNum);
            btn.disabled = isDisabled;
            return btn;
        };

        pagDiv.appendChild(createButton('<', state.currentPage - 1, false, state.currentPage === 1));
        
        // Simplified page number display
        let pagesToShow = [1, state.currentPage, totalPages].filter((v, i, a) => a.indexOf(v) === i).sort((a, b) => a - b);
        if (state.currentPage > 2) pagesToShow.splice(1, 0, '...');
        if (state.currentPage < totalPages - 1) pagesToShow.splice(pagesToShow.length - 1, 0, '...');
        
        pagesToShow.forEach(p => {
            if(p === '...') {
                const span = document.createElement('span');
                span.textContent = '...';
                span.className = 'pagination-ellipsis';
                pagDiv.appendChild(span);
            } else {
                 pagDiv.appendChild(createButton(p, p, p === state.currentPage));
            }
        });
        
        pagDiv.appendChild(createButton('>', state.currentPage + 1, false, state.currentPage === totalPages));
    }

    function applyCombinedFilters() {
        const globalSearchTerm = document.getElementById('globalSearch')?.value.toUpperCase() || '';
        for (const tableId in tableSearchConfigs) {
            const config = tableSearchConfigs[tableId];
            const state = paginatedTables[tableId];
            if (!state) continue;

            const individualSearchTerm = document.getElementById(config.individualInputId)?.value.toUpperCase() || '';
            const typeFilterValue = document.getElementById(config.typeFilterDropdownId)?.value.toUpperCase() || '';
            
            state.filteredRows = state.originalRows.filter(row => {
                if (row.cells.length < 2) return false; // Skip no-data rows

                const matchesIndividual = !individualSearchTerm || config.searchCols.some(colIdx => 
                    row.cells[colIdx]?.textContent.toUpperCase().includes(individualSearchTerm)
                );

                const matchesGlobal = !globalSearchTerm || Array.from(row.cells).some(cell => 
                    cell.textContent.toUpperCase().includes(globalSearchTerm)
                );
                
                const matchesType = !typeFilterValue || (row.cells[config.typeColumnIndex]?.textContent.toUpperCase() === typeFilterValue);

                return matchesIndividual && matchesGlobal && matchesType;
            });
            showPage(tableId, 1);
        }
    }

    function updateNoDataRowVisibility(tableId) {
        const table = document.getElementById(tableId);
        const tbody = table?.querySelector("tbody");
        if(!tbody) return;
        const state = paginatedTables[tableId];

        let noDataRow = tbody.querySelector('tr.no-data-row');
        const hasVisibleData = state && state.filteredRows.length > 0;
        
        if (!hasVisibleData) {
            if (!noDataRow) {
                noDataRow = tbody.insertRow();
                noDataRow.className = 'no-data-row';
                const cell = noDataRow.insertCell();
                cell.colSpan = table.querySelector('thead th')?.length || 5;
                cell.textContent = "ไม่มีข้อมูลที่ตรงกับเงื่อนไข";
                cell.style.textAlign = "center";
            }
            noDataRow.style.display = '';
        } else if (noDataRow) {
            noDataRow.style.display = 'none';
        }
    }
    
    // --- 9. Global Message & Initialization ---
    function displayGlobalMessage(type, text) {
        const container = document.getElementById('globalMessages');
        if (!container) return;
        const message = document.createElement('div');
        message.className = `message ${type}`;
        message.textContent = text;
        container.innerHTML = '';
        container.appendChild(message);
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    }
    
    // Setup event listeners for all search inputs
    document.getElementById('globalSearch')?.addEventListener('keyup', applyCombinedFilters);
    for (const tableId in tableSearchConfigs) {
        const config = tableSearchConfigs[tableId];
        document.getElementById(config.individualInputId)?.addEventListener('keyup', applyCombinedFilters);
        document.getElementById(config.typeFilterDropdownId)?.addEventListener('change', applyCombinedFilters);
    }

    // Initialize all components
    initPagination('rulesTable', 5);
    initPagination('materialsTable', 5);
    initPagination('optionsTable', 5);
    initPagination('stockManagementTable', 20);
    setupStockControls();
    activateSectionFromHash(); // Activate section based on URL hash
    applyCombinedFilters(); // Apply initial filter state
});