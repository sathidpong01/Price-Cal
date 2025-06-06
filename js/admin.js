// js/admin.js

// CHANGED/ADDED: Wrap existing code in DOMContentLoaded or ensure it runs after DOM is ready
document.addEventListener('DOMContentLoaded', function() {

    // --- CHANGED/ADDED: Sidebar Navigation Logic ---
    const sidebarLinks = document.querySelectorAll('.sidebar-menu .sidebar-link');
    const contentPanels = document.querySelectorAll('.main-content-container .content-panel');
    const stockTypeFilterDropdown = document.getElementById('stockTypeFilter'); // << ADDED for stock filter

    function showContentPanel(panelIdToShow) {
        contentPanels.forEach(panel => {
            if (panel.id === panelIdToShow) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        });
    }

    function setActiveLink(linkToActivate) {
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
        });
        if (linkToActivate) {
            linkToActivate.classList.add('active');
        }
    }

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            // We don't preventDefault if href is a hash, to allow URL to update
            // event.preventDefault(); 
            const targetPanelId = this.getAttribute('data-section-id');

            if (targetPanelId && document.getElementById(targetPanelId)) {
                showContentPanel(targetPanelId);
                setActiveLink(this);

                // Ensure pagination/filters are re-applied to the newly shown table
                const activePanel = document.getElementById(targetPanelId);
                if (activePanel) {
                    activePanel.querySelectorAll('table[id]').forEach(table => {
                        if (paginatedTables[table.id]) {
                            // A full re-filter will also re-paginate.
                            applyCombinedFilters(); 
                        } else {
                            // If table was not initialized (e.g. first load of a specific panel via hash)
                            // You might need to call initPagination here for that specific table if it wasn't done globally.
                            // For simplicity, ensure all tables are initialized once at the start.
                        }
                    });
                }
            }
        });
    });

    function activateSectionFromHash() {
        const hash = window.location.hash; // e.g., #rules
        if (hash) {
            const link = document.querySelector(`.sidebar-link[href="${hash}"]`);
            if (link) {
                const targetPanelId = link.getAttribute('data-section-id');
                if (document.getElementById(targetPanelId)) {
                    showContentPanel(targetPanelId);
                    setActiveLink(link);
                    return true;
                }
            }
        }
        return false;
    }

    // Show initial panel
    if (!activateSectionFromHash()) {
        const firstLink = document.querySelector('.sidebar-menu .sidebar-link');
        if (firstLink) {
            const firstPanelId = firstLink.getAttribute('data-section-id');
            if (document.getElementById(firstPanelId)) {
                showContentPanel(firstPanelId);
                setActiveLink(firstLink);
                // firstLink.classList.add('active'); // setActiveLink already does this
            }
        }
    }
    // --- END: Sidebar Navigation Logic ---


    // --- EXISTING CODE (pagination, modals, delete, edit, filters, etc.) ---
    function formatNumber(num) {
        try {
            let parsedNum = parseFloat(num);
            if (isNaN(parsedNum)) return '0.00';
            return parsedNum.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } catch(e) { return '0.00'; }
    } //

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function (match) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match];
        });
    } //

    function updateTableRow(type, data) {
        console.log("[DEBUG] Updating table row:", type, data);
        let rowId = '';
        let idField = '';

        if (type === 'rule') { idField = 'rule_id'; }
        else if (type === 'material') { idField = 'material_id'; }
        else if (type === 'option') { idField = 'option_id'; }
        else if (type === 'stock') { idField = 'stock_id'; } // Added for stock
        else { console.error("Unknown type for updateTableRow:", type); return; }

        if (!data || !data[idField]) {
             console.error("Invalid data or missing ID provided to updateTableRow:", data);
             return;
        }

        rowId = `${type}-row-${data[idField]}`;
        const row = document.getElementById(rowId);
        if (!row) {
            console.warn("Row not found for update, possibly new item, skipping direct row update:", rowId);
            // For new items, a full table reload/re-fetch or manual row append might be needed
            // Or simply rely on the user refreshing or the next filter action repopulating.
            // For simplicity in this context, we won't add dynamic row creation here.
            // A page reload or re-filtering would show the new item.
            return;
        }

        row.querySelectorAll('td[data-field]').forEach(cell => {
            const field = cell.getAttribute('data-field');
            let newValue = '';

            if (type === 'rule') {
                if (field === 'name') newValue = data.rule_name;
                else if (field === 'value') newValue = formatNumber(data.rule_value);
                else if (field === 'unit') newValue = data.rule_unit;
            } else if (type === 'material') {
                if (field === 'type') newValue = data.product_type;
                else if (field === 'name') newValue = data.material_name;
                else if (field === 'price') newValue = formatNumber(data.price_per_unit);
                else if (field === 'unit') newValue = data.unit;
            } else if (type === 'option') {
                if (field === 'name') newValue = data.option_name;
                else if (field === 'price') newValue = formatNumber(data.option_price);
                else if (field === 'category') newValue = data.category || 'ทั่วไป';
            } else if (type === 'stock') { // Added for stock
                if (field === 'product_type') newValue = data.product_type;
                else if (field === 'product_name') newValue = data.product_name;
                // Quantity is an input field, handle separately or ensure it's updated if data-field is used
                else if (field === 'unit') newValue = data.unit;
            }
            if (cell.querySelector('input')) { // If cell contains an input (like stock quantity)
                 if (type === 'stock' && field === 'quantity') {
                    cell.querySelector('input').value = data.quantity;
                    cell.querySelector('input').setAttribute('data-initial-value', data.quantity);
                 }
            } else {
                cell.textContent = htmlspecialchars(newValue);
            }
        });
        // If it's a stock update, also update the input field directly if not handled by data-field
        if (type === 'stock') {
            const quantityInput = row.querySelector('.stock-quantity-input');
            if (quantityInput && data.hasOwnProperty('quantity')) {
                quantityInput.value = data.quantity;
                quantityInput.setAttribute('data-initial-value', data.quantity);
                row.classList.remove('changed'); // Reset changed state for stock
            }
        }


        row.style.transition = 'background-color 0.2s ease-out';
        row.style.backgroundColor = '#d4edda'; // Success highlight
        setTimeout(() => {
            row.style.backgroundColor = '';
            row.style.transition = '';
        }, 2000);
    } //

    function confirmDelete(type, nameOrId) {
        return confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${type} '${nameOrId}' ? การกระทำนี้ไม่สามารถย้อนกลับได้!`);
    } //

    // Expose to global scope for inline onclick attributes
    window.handleDeleteClick = function(type, id, name) {
        if (confirmDelete(type, name)) {
            performAjaxDelete(type, id);
        }
    }; //

    function performAjaxDelete(type, id) {
        const formData = new FormData();
        formData.append('action', `delete_${type}`);
        // For stock, the ID is 'stock_id', but admin_ajax_data_handler expects 'id' generally
        // So, if type is 'stock', make sure 'id' carries stock_id. This should be fine.
        formData.append('id', id);

        fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayGlobalMessage('success', data.message || `ลบ ${type} เรียบร้อยแล้ว`);
                const rowId = `${type}-row-${id}`;
                const row = document.getElementById(rowId);
                if (row) {
                    const table = row.closest('table');
                    if (table) {
                        const tableId = table.id;
                        row.style.transition = 'background-color 0.3s ease-out, opacity 0.5s ease-in 0.3s';
                        row.style.backgroundColor = '#f8d7da'; // Highlight delete
                        setTimeout(() => {
                            row.style.opacity = '0';
                            setTimeout(() => {
                                const state = paginatedTables[tableId];
                                if (state) {
                                    state.originalRows = state.originalRows.filter(r => r !== row); // Remove from originalRows
                                    state.filteredRows = state.filteredRows.filter(r => r !== row); // Remove from filteredRows
                                }
                                row.remove();
                                if (state) {
                                     showPage(tableId, state.currentPage); // Repaginate after delete
                                     updateNoDataRowVisibility(tableId);
                                }
                            }, 500);
                        }, 300);
                    }
                }
            } else {
                displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาดในการลบ');
            }
        })
        .catch(error => {
            console.error('Error during AJAX delete:', error);
            displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อลบ');
        });
    } //

    // Expose to global scope
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    }; //

    window.openEditModal = function(type, id) {
        console.log(`[DEBUG] 1. Attempting to open modal for type: ${type}, id: ${id}`);
        let modalId = ''; let formId = '';
        let url = `admin_ajax_data_handler.php?action=get_${type}_data&id=${id}`;

        if (type === 'rule') { modalId = 'editRuleModal'; formId = 'editRuleForm'; }
        else if (type === 'material') { modalId = 'editMaterialModal'; formId = 'editMaterialForm'; }
        else if (type === 'option') { modalId = 'editOptionModal'; formId = 'editOptionForm'; }
        else if (type === 'stock') { modalId = 'editStockModal'; formId = 'editStockForm'; }
        else { console.error('[DEBUG] Unknown modal type:', type); return; }

        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        if (!modal || !form) { console.error('[DEBUG] Modal or Form not found! Modal ID:', modalId, 'Form ID:', formId); return; }

        console.log(`[DEBUG] 2. Fetching data from: ${url}`);
        fetch(url)
            .then(response => {
                console.log('[DEBUG] 3. Fetch response status:', response.status);
                if (!response.ok) { throw new Error('Network response was not ok ' + response.statusText); }
                return response.json();
            })
            .then(data => {
                console.log('[DEBUG] 4. Fetch data received:', data);
                if (data.success && data.data) {
                    const itemData = data.data;
                    // Use hidden input for ID in each form
                    form.elements[`${type}_id`].value = itemData[`${type}_id`]; // e.g., rule_id, material_id

                    if (type === 'rule') {
                        form.elements['rule_name'].value = itemData.rule_name;
                        form.elements['rule_value'].value = parseFloat(itemData.rule_value).toFixed(2);
                        form.elements['rule_unit'].value = itemData.rule_unit;
                    } else if (type === 'material') {
                        form.elements['material_type'].value = itemData.product_type;
                        form.elements['material_name'].value = itemData.material_name;
                        form.elements['material_price'].value = parseFloat(itemData.price_per_unit).toFixed(2);
                        form.elements['material_unit'].value = itemData.unit;
                    } else if (type === 'option') {
                        form.elements['option_name'].value = itemData.option_name;
                        form.elements['option_price'].value = parseFloat(itemData.option_price).toFixed(2);
                        form.elements['option_category'].value = itemData.category || 'ทั่วไป';
                    } else if (type === 'stock') {
                        form.elements['product_name'].value = itemData.product_name;
                        form.elements['product_type'].value = itemData.product_type;
                        form.elements['quantity'].value = itemData.quantity;
                        form.elements['unit'].value = itemData.unit;
                    }
                    console.log(`[DEBUG] 5. Setting modal display to "block" for:`, modalId);
                    modal.style.display = "block";
                    const firstInput = form.querySelector('input[type="text"], input[type="number"], select, textarea');
                    if (firstInput) firstInput.focus();
                } else {
                    console.error('[DEBUG] 5b. Fetch successful but data error:', data.error);
                    displayGlobalMessage('error', 'ไม่สามารถดึงข้อมูลได้: ' + (data.error || 'ไม่ทราบสาเหตุ'));
                }
            })
            .catch(error => {
                console.error('[DEBUG] 6. Error fetching data for modal:', error);
                displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' + error.message);
            });
    }; //

    function submitModalForm(formElement, url, modalId) { // Removed sectionToReload
        const formData = new FormData(formElement);
        const typeAction = formData.get('action'); // e.g., update_rule
        const itemType = typeAction.replace('update_', ''); // e.g., rule

        fetch(url, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal(modalId);
                displayGlobalMessage('success', data.message || 'บันทึกข้อมูลเรียบร้อย!');
                if (data.data) {
                    updateTableRow(itemType, data.data);
                } else {
                     console.warn("No data returned from modal submit, table row not updated directly.");
                     // Consider re-filtering to show changes if no data.data is returned
                     // applyCombinedFilters();
                }
            } else {
                displayGlobalMessage('error', 'เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถบันทึกข้อมูลได้'), modalId);
            }
        })
        .catch(error => {
            console.error('Error submitting modal form:', error);
            displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error.message, modalId);
        });
    } //

    function displayGlobalMessage(type, text, modalIdToKeepOpen = null) {
        const messagesDivContainer = document.getElementById('globalMessages'); // Container for messages
        if (!messagesDivContainer) {
            console.error("Global messages container not found!");
            alert(`${type}: ${text}`); // Fallback to alert
            return;
        }
        // Create a new message element each time
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.innerHTML = text; // Use innerHTML to allow basic HTML in messages

        // Clear previous messages before appending a new one
        messagesDivContainer.innerHTML = '';
        messagesDivContainer.appendChild(messageElement);
        messageElement.style.display = 'block'; // Ensure it's visible

        if (type === 'success' || !modalIdToKeepOpen) {
             setTimeout(() => {
                 // Fade out then remove
                 messageElement.style.transition = 'opacity 0.5s ease';
                 messageElement.style.opacity = '0';
                 setTimeout(() => {
                    if(messageElement && messageElement.parentNode === messagesDivContainer) {
                         messagesDivContainer.removeChild(messageElement);
                    }
                 }, 500);
             }, 5000); // Message visible for 5 seconds
        }
    } //

    // --- Pagination and Filter Functions ---
    let paginatedTables = {}; //
    function initPagination(tableId, rowsPerPage = 5) { // Default to 5 if not specified
        const table = document.getElementById(tableId);
        if (!table) { console.warn(`Table with id ${tableId} not found for pagination.`); return; }
        const tbody = table.querySelector('tbody');
        if (!tbody) { console.warn(`Tbody not found in table ${tableId}.`); return; }
        
        // Detach all rows first to handle potential re-initialization
        const rows = Array.from(tbody.querySelectorAll('tr'));
        // rows.forEach(row => tbody.removeChild(row)); // Keep rows in tbody, just hide/show

        paginatedTables[tableId] = {
            rowsPerPage: rowsPerPage,
            currentPage: 1,
            originalRows: rows, // Store all rows once
            filteredRows: rows  // Initially all rows are filtered rows
        };
        // console.log(`Initialized pagination for ${tableId}:`, paginatedTables[tableId]);
        showPage(tableId, 1);
        updateNoDataRowVisibility(tableId); // Update "no data" row visibility
    } //

    function showPage(tableId, page) {
        const state = paginatedTables[tableId];
        if (!state) { console.warn(`State for table ${tableId} not found in showPage.`); return; }

        const rowsToPaginate = state.filteredRows; // Paginate based on currently filtered rows
        const totalRows = rowsToPaginate.length;
        const rowsPerPage = state.rowsPerPage;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        state.currentPage = Math.max(1, Math.min(page, totalPages));

        // Hide all original rows first, then show only the ones for the current page from filteredRows
        state.originalRows.forEach(row => row.style.display = 'none');

        const startIndex = (state.currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;

        rowsToPaginate.slice(startIndex, endIndex).forEach(row => {
            row.style.display = ''; // Empty string for default display (e.g., 'table-row')
        });
        
        updatePaginationControls(tableId);
        updateNoDataRowVisibility(tableId); // Also update "no data" row after showing page
    } //

    function updatePaginationControls(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const state = paginatedTables[tableId];
        if (!state) return;

        const rows = state.filteredRows;
        const rowsPerPage = state.rowsPerPage;
        const totalPages = Math.ceil(rows.length / rowsPerPage) || 1;
        const currentPage = state.currentPage;

        let tableResponsiveContainer = table.closest('.table-responsive');
        if (!tableResponsiveContainer) tableResponsiveContainer = table.parentNode; // Fallback

        let pagDiv = tableResponsiveContainer.querySelector('.pagination');
        if (!pagDiv) {
            pagDiv = document.createElement('div');
            pagDiv.className = 'pagination';
            // Insert after the table, or at the end of table-responsive
            tableResponsiveContainer.appendChild(pagDiv);
        }
        pagDiv.innerHTML = '';

        if (totalPages <= 1) {
            pagDiv.style.display = 'none';
            return;
        } else {
            pagDiv.style.display = 'flex'; // Ensure it's visible
        }

        // Simple pagination: Prev, 1, 2, ..., Next
        // Or more complex if needed
        const createButton = (text, pageNum, isActive = false, isDisabled = false) => {
            let btn = document.createElement('button');
            btn.className = 'pagination-btn' + (isActive ? ' active' : '');
            btn.textContent = text;
            btn.onclick = () => showPage(tableId, pageNum);
            if (isDisabled) btn.disabled = true;
            return btn;
        };

        // Prev button
        pagDiv.appendChild(createButton('<<', 1, false, currentPage === 1));
        pagDiv.appendChild(createButton('<', currentPage - 1, false, currentPage === 1));

        // Page numbers (simplified for brevity, can be expanded)
        // Show current page, and +/- 1 or 2 pages around it
        let startPage = Math.max(1, currentPage - 1);
        let endPage = Math.min(totalPages, currentPage + 1);
        if (currentPage === 1) endPage = Math.min(totalPages, 3);
        if (currentPage === totalPages) startPage = Math.max(1, totalPages - 2);


        if (startPage > 1) {
             // pagDiv.appendChild(createButton('1', 1)); // Already handled by <<
             if (startPage > 2) pagDiv.appendChild(document.createTextNode('...'));
        }

        for (let i = startPage; i <= endPage; i++) {
            pagDiv.appendChild(createButton(i, i, i === currentPage));
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) pagDiv.appendChild(document.createTextNode('...'));
            // pagDiv.appendChild(createButton(totalPages, totalPages)); // Already handled by >>
        }
        // Next button
        pagDiv.appendChild(createButton('>', currentPage + 1, false, currentPage === totalPages));
        pagDiv.appendChild(createButton('>>', totalPages, false, currentPage === totalPages));

    } //

    function repaginate(tableId) { // Kept for explicitness if called from somewhere else
        showPage(tableId, paginatedTables[tableId]?.currentPage || 1);
    } //


    // --- Form Submit Event Listeners ---
    document.getElementById('editRuleForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', 'editRuleModal');
    }); //
    document.getElementById('editMaterialForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', 'editMaterialModal');
    }); //
    document.getElementById('editOptionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', 'editOptionModal');
    }); //
    document.getElementById('editStockForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', 'editStockModal');
    }); //


    // --- Display Toggles ---
    document.querySelectorAll('.rule-display-toggle, .material-display-toggle, .option-display-toggle').forEach(toggleInput => {
        toggleInput.addEventListener('change', function() {
            let type = '';
            let id = 0;
            let name = ''; // For message

            if (this.classList.contains('rule-display-toggle')) {
                type = 'rule'; id = this.dataset.ruleId;
                name = this.closest('tr').querySelector('td[data-field="name"]')?.textContent || id;
            } else if (this.classList.contains('material-display-toggle')) {
                type = 'material'; id = this.dataset.materialId;
                name = this.closest('tr').querySelector('td[data-field="name"]')?.textContent || id;
            } else if (this.classList.contains('option-display-toggle')) {
                type = 'option'; id = this.dataset.optionId;
                name = this.closest('tr').querySelector('td[data-field="name"]')?.textContent || id;
            }

            if (type && id) {
                const isVisible = this.checked;
                fetch('admin_ajax_data_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update_display_status&type=${type}&id=${id}&visible=${isVisible ? 1 : 0}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusText = isVisible ? "แสดง" : "ซ่อน";
                        displayGlobalMessage('success', `สถานะของ '${data.name || name}' ถูกอัปเดตเป็น ${statusText} ในเครื่องคำนวณแล้ว`);
                    } else {
                        displayGlobalMessage('error', `ผิดพลาดในการอัปเดตสถานะ: ${data.error || 'Unknown error'}`);
                        this.checked = !isVisible; // Revert checkbox on error
                    }
                })
                .catch(error => {
                    console.error('Fetch error for display status:', error);
                    displayGlobalMessage('error', 'การเชื่อมต่อล้มเหลว ไม่สามารถอัปเดตสถานะได้');
                    this.checked = !isVisible; // Revert checkbox
                });
            } else {
                console.error('Could not determine type or id for display toggle:', this);
            }
        });
    }); //


    // --- Stock Update Logic ---
    window.handleStockUpdate = function(stockId, buttonElement) {
        const row = buttonElement.closest('tr');
        if (!row) return;
        const input = row.querySelector('.stock-quantity-input');
        if (!input) return;
        const newQuantity = input.value;
    
        const formData = new FormData();
        formData.append('action', 'update_stock_quantity'); // <-- แก้ไขเป็นชื่อ action ใหม่
        formData.append('stock_id', stockId);
        formData.append('quantity', newQuantity);
    
        fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    input.setAttribute('data-initial-value', data.data.quantity);
                    row.classList.remove('changed');
                    // ซ่อนปุ่มหลังจากบันทึกสำเร็จ
                    const saveButton = row.querySelector('.btn-stock-save');
                    if (saveButton) {
                        saveButton.style.visibility = 'hidden';
                        saveButton.style.opacity = '0';
                    }
                    displayGlobalMessage('success', data.message || 'อัปเดตจำนวนสต็อกเรียบร้อย!');
                } else {
                    displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาดในการอัปเดตสต็อก');
                }
            })
            .catch(error => {
                console.error('Error updating stock quantity:', error);
                displayGlobalMessage('error', 'การเชื่อมต่อล้มเหลว ไม่สามารถอัปเดตสต็อกได้');
            });
    };

    function setupStockControls() {
        const stockTable = document.getElementById('stockManagementTable');
        if (!stockTable) return;

        stockTable.querySelectorAll('tbody tr').forEach(row => {
            const input = row.querySelector('.stock-quantity-input');
            const saveButton = row.querySelector('.btn-stock-save');
            if (input && saveButton) {
                input.addEventListener('input', () => {
                    if (input.value !== input.getAttribute('data-initial-value')) {
                        row.classList.add('changed');
                        saveButton.style.visibility = 'visible';
                        saveButton.style.opacity = '1';
                    } else {
                        row.classList.remove('changed');
                        saveButton.style.visibility = 'hidden';
                        saveButton.style.opacity = '0';
                    }
                });
            }
        });
    } //


    // --- Table Search Filters ---
    // << CHANGED/ADDED: Updated tableSearchConfigs and applyCombinedFilters for stock type filter >>
    const tableSearchConfigs = {
        'rulesTable': { individualInputId: 'ruleSearch', searchCols: [1, 3] }, // Col indices: ชื่อกฎ, หน่วย
        'materialsTable': { individualInputId: 'materialSearch', searchCols: [1, 2, 4] }, // ประเภท, ชื่อวัสดุ, หน่วย
        'optionsTable': { individualInputId: 'optionSearch', searchCols: [1, 3] }, // ชื่อออปชัน, หมวดหมู่
        'stockManagementTable': {
            individualInputId: 'stockSearch',       // Text search input ID
            searchCols: [2, 4],                     // Columns for text search (Name, Unit)
            typeFilterDropdownId: 'stockTypeFilter',// ID of the type filter dropdown
            typeColumnIndex: 1                      // Column index for "Product Type" in stock table
        }
    }; //

    function applyCombinedFilters() {
        const globalSearchTerm = document.getElementById('globalSearch')?.value.toUpperCase() || '';

        for (const tableId in tableSearchConfigs) {
            const config = tableSearchConfigs[tableId];
            const tableElement = document.getElementById(tableId);

            // Optimization: Only filter tables that are currently visible in the active content panel
            if (tableElement) {
                const parentPanel = tableElement.closest('.content-panel');
                if (parentPanel && parentPanel.style.display === 'none') {
                    continue; // Skip filtering for hidden tables
                }
            }


            let individualSearchTerm = '';
            if (config.individualInputId) {
                const individualInput = document.getElementById(config.individualInputId);
                if (individualInput) {
                    individualSearchTerm = individualInput.value.toUpperCase();
                }
            }

            let selectedTypeFilter = '';
            if (config.typeFilterDropdownId) {
                const typeDropdown = document.getElementById(config.typeFilterDropdownId);
                if (typeDropdown) {
                    selectedTypeFilter = typeDropdown.value.toUpperCase();
                }
            }

            const state = paginatedTables[tableId];
            if (!state || !state.originalRows) { // Ensure originalRows exists
                // console.warn(`Pagination state or originalRows not found for table: ${tableId}.`);
                continue;
            }

            const newFilteredRows = [];
            for (const row of state.originalRows) {
                // Skip "no data" rows or invalid rows
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                    // This is a "no data" row, it's handled by updateNoDataRowVisibility
                    continue;
                }
                // Ensure row has enough cells for configured column checks
                const maxColIndex = Math.max(...config.searchCols, (config.typeColumnIndex !== undefined ? config.typeColumnIndex : -1));
                if (row.cells.length <= maxColIndex && maxColIndex !== -1) {
                    continue; 
                }


                let matchesType = !selectedTypeFilter; // True if no type filter selected
                if (selectedTypeFilter && config.hasOwnProperty('typeColumnIndex')) {
                    if (row.cells[config.typeColumnIndex]) {
                        const typeValue = (row.cells[config.typeColumnIndex].textContent || row.cells[config.typeColumnIndex].innerText).toUpperCase();
                        matchesType = (typeValue === selectedTypeFilter);
                    } else {
                        matchesType = false; // Column doesn't exist for type check
                    }
                }

                let matchesIndividual = !individualSearchTerm;
                if (individualSearchTerm) {
                    matchesIndividual = false;
                    for (const colIndex of config.searchCols) {
                        if (row.cells[colIndex]) {
                            const txtValue = (row.cells[colIndex].textContent || row.cells[colIndex].innerText).toUpperCase();
                            if (txtValue.indexOf(individualSearchTerm) > -1) {
                                matchesIndividual = true;
                                break;
                            }
                        }
                    }
                }

                let matchesGlobal = !globalSearchTerm;
                if (globalSearchTerm) {
                    matchesGlobal = false;
                    for (let i = 0; i < row.cells.length; i++) { // Search all cells for global term
                        if (row.cells[i]) {
                             const txtValue = (row.cells[i].textContent || row.cells[i].innerText).toUpperCase();
                             if (txtValue.indexOf(globalSearchTerm) > -1) {
                                 matchesGlobal = true;
                                 break;
                             }
                        }
                    }
                }

                if (matchesIndividual && matchesGlobal && matchesType) {
                    newFilteredRows.push(row);
                }
            }
            state.filteredRows = newFilteredRows;
            showPage(tableId, 1); // This will also update pagination controls and "no data" visibility
        }
    } //

    function updateNoDataRowVisibility(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.querySelector("tbody");
        if (!tbody) return;

        const state = paginatedTables[tableId];
        const hasVisibleDataRows = state && state.filteredRows && state.filteredRows.length > 0;

        let noDataRowElement = tbody.querySelector('tr.no-data-row'); // Look for a specific class

        if (!noDataRowElement) { // Fallback: create or find the "colspan" row
            for (const row of Array.from(tbody.childNodes)) { // Iterate childNodes to include text nodes if any for robust check
                if (row.nodeType === Node.ELEMENT_NODE && row.cells && row.cells.length === 1 && row.cells[0].colSpan > 1) {
                    noDataRowElement = row;
                    break;
                }
            }
        }
        
        if (hasVisibleDataRows) {
            if (noDataRowElement) noDataRowElement.style.display = "none";
        } else { // No visible data rows
            if (!noDataRowElement) { // If "no data" row doesn't exist, create it
                const headerCells = table.querySelector("thead tr")?.cells.length || 1;
                noDataRowElement = tbody.insertRow();
                noDataRowElement.classList.add('no-data-row'); // Add class for easier selection
                const cell = noDataRowElement.insertCell();
                cell.colSpan = headerCells;
                cell.textContent = "ไม่มีข้อมูล";
                cell.style.textAlign = "center";
                cell.style.padding = "20px";
                cell.style.color = "#777";
            }
            noDataRowElement.style.display = ""; // Show it (or 'table-row')
        }
    } //


    // Setup event listeners for search inputs
    const globalSearchInput = document.getElementById('globalSearch');
    if (globalSearchInput) {
        globalSearchInput.addEventListener('keyup', applyCombinedFilters);
    }

    for (const tableId in tableSearchConfigs) {
        const config = tableSearchConfigs[tableId];
        if (config.individualInputId) {
            const individualInput = document.getElementById(config.individualInputId);
            if (individualInput) {
                individualInput.addEventListener('keyup', applyCombinedFilters);
            }
        }
        // << ADDED: Event listener for stock type filter dropdown if it exists >>
        if (config.typeFilterDropdownId) {
            const typeDropdown = document.getElementById(config.typeFilterDropdownId);
            if (typeDropdown) {
                typeDropdown.addEventListener('change', applyCombinedFilters);
            }
        }
    }

    // Initialize components
    // initPagination for all tables first
    initPagination('rulesTable', 5);
    initPagination('materialsTable', 5);
    initPagination('optionsTable', 5);
    initPagination('stockManagementTable', 10);

    setupStockControls(); //

    // Then apply filters which will also trigger showPage
    applyCombinedFilters(); // Initial filter application for the visible panel

}); // END DOMContentLoaded