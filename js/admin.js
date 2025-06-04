// js/admin.js (เวอร์ชันแก้ไข Bug + Debug Log)

function formatNumber(num) {
    try {
        let parsedNum = parseFloat(num);
        if (isNaN(parsedNum)) return '0.00';
        return parsedNum.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch(e) { return '0.00'; }
}

function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function (match) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match];
    });
}

function updateTableRow(type, data) {
    console.log("[DEBUG] Updating table row:", type, data);
    let rowId = '';
    let idField = '';

    if (type === 'rule') { idField = 'rule_id'; }
    else if (type === 'material') { idField = 'material_id'; }
    else if (type === 'option') { idField = 'option_id'; }
    else { console.error("Unknown type for updateTableRow:", type); return; }

    if (!data || !data[idField]) {
         console.error("Invalid data or missing ID provided to updateTableRow:", data);
         return;
    }

    rowId = `${type}-row-${data[idField]}`;
    const row = document.getElementById(rowId);
    if (!row) {
        console.error("Row not found:", rowId);
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
            else if (field === 'category') newValue = data.category; // เพิ่มการอัปเดต category
        }

        cell.textContent = htmlspecialchars(newValue);
    });

    row.style.transition = 'background-color 0.2s ease-out';
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        row.style.backgroundColor = '';
        row.style.transition = '';
    }, 2000);
}

function confirmDelete(type, nameOrId) {
    return confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${type} '${nameOrId}' ? การกระทำนี้ไม่สามารถย้อนกลับได้!`);
}

function handleDeleteClick(type, id, name) {
    if (confirmDelete(type, name)) {
        performAjaxDelete(type, id);
    }
}

function performAjaxDelete(type, id) {
    const formData = new FormData();
    formData.append('action', `delete_${type}`);
    formData.append('id', id);

    fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayGlobalMessage('success', data.message);
            const rowId = `${type}-row-${id}`;
            const row = document.getElementById(rowId);
            const tableId = row.closest('table').id;

            if (row) {
                row.style.transition = 'background-color 0.3s ease-out, opacity 0.5s ease-in 0.3s';
                row.style.backgroundColor = '#f8d7da';
                setTimeout(() => {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        const state = paginatedTables[tableId];
                        if (state) {
                            state.originalRows = state.originalRows.filter(r => r.id !== rowId);
                        }
                        row.remove();
                        repaginate(tableId);
                    }, 500);
                }, 300);
            }
        } else {
            displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาดในการลบ');
        }
    })
    .catch(error => {
        console.error('Error during AJAX delete:', error);
        displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อลบ');
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "none";
}

function openEditModal(type, id) {
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
                if (type === 'rule') {
                    form.elements['rule_id'].value = itemData.rule_id;
                    form.elements['rule_name'].value = itemData.rule_name;
                    form.elements['rule_value'].value = parseFloat(itemData.rule_value).toFixed(2);
                    form.elements['rule_unit'].value = itemData.rule_unit;
                } else if (type === 'material') {
                    form.elements['material_id'].value = itemData.material_id;
                    form.elements['material_type'].value = itemData.product_type;
                    form.elements['material_name'].value = itemData.material_name;
                    form.elements['material_price'].value = parseFloat(itemData.price_per_unit).toFixed(2);
                    form.elements['material_unit'].value = itemData.unit;
                } else if (type === 'option') {
                    form.elements['option_id'].value = itemData.option_id;
                    form.elements['option_name'].value = itemData.option_name;
                    form.elements['option_price'].value = parseFloat(itemData.option_price).toFixed(2);
                    form.elements['option_category'].value = itemData.category || 'ทั่วไป';
                } else if (type === 'stock') {
                    form.elements['stock_id'].value = itemData.stock_id;
                    form.elements['product_name'].value = itemData.product_name;
                    form.elements['product_type'].value = itemData.product_type;
                    form.elements['quantity'].value = itemData.quantity;
                    form.elements['unit'].value = itemData.unit;
                }
                console.log(`[DEBUG] 5. Setting modal display to "block" for:`, modalId);
                modal.style.display = "block";
                const firstInput = form.querySelector('input[type="text"], input[type="number"], select');
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
}

function submitModalForm(formElement, url, sectionToReload, modalId) {
    const formData = new FormData(formElement);
    const type = modalId.replace('edit', '').replace('Modal', '').toLowerCase();
    fetch(url, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal(modalId);
            displayGlobalMessage('success', data.message || 'บันทึกข้อมูลเรียบร้อย!');
            if (data.data) {
                updateTableRow(type, data.data);
            } else {
                console.warn("No data returned, considering reload.");
            }
        } else {
            displayGlobalMessage('error', 'เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถบันทึกข้อมูลได้'), modalId);
        }
    })
    .catch(error => {
        console.error('Error submitting modal form:', error);
        displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error.message);
    });
}

function displayGlobalMessage(type, text, modalIdToKeepOpen = null) {
    const messagesDiv = document.getElementById('globalMessages');
    if (!messagesDiv) return;
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.innerHTML = text;
    messagesDiv.innerHTML = '';
    messagesDiv.appendChild(messageElement);
    if (type === 'success' || !modalIdToKeepOpen) {
         setTimeout(() => {
             if(messageElement) messageElement.style.display = 'none';
         }, 5000);
    }
}

// --- Pagination and Filter Functions (โค้ดส่วนนี้ทั้งหมดเหมือนเดิม) ---
let paginatedTables = {};
function initPagination(tableId, rowsPerPage) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    paginatedTables[tableId] = {
        rowsPerPage: rowsPerPage,
        currentPage: 1,
        originalRows: rows,
        filteredRows: rows // เริ่มต้นคือทุกแถว
    };
    showPage(tableId, 1);
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
    rows.forEach((row, idx) => {
        row.style.display = (idx >= (state.currentPage - 1) * rowsPerPage && idx < state.currentPage * rowsPerPage) ? '' : 'none';
    });
    updatePaginationControls(tableId);
}

function updatePaginationControls(tableId) {
    let table = document.getElementById(tableId);
    if (!table) return;
    let state = paginatedTables[tableId];
    if (!state) return;
    let rows = state.filteredRows;
    let rowsPerPage = state.rowsPerPage;
    let totalPages = Math.ceil(rows.length / rowsPerPage) || 1;
    let currentPage = state.currentPage;
    let tableResponsive = table.closest('.table-responsive');
    if (!tableResponsive) tableResponsive = table.parentNode;
    let pagDiv = tableResponsive.querySelector('.pagination');
    if (!pagDiv) {
        pagDiv = document.createElement('div');
        pagDiv.className = 'pagination';
        tableResponsive.appendChild(pagDiv);
    }
    pagDiv.innerHTML = '';
    if (totalPages <= 1) {
        pagDiv.style.display = 'none';
        return;
    } else {
        pagDiv.style.display = 'flex';
    }
    for (let i = 1; i <= totalPages; i++) {
        let btn = document.createElement('button');
        btn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');
        btn.textContent = i;
        btn.onclick = () => showPage(tableId, i);
        pagDiv.appendChild(btn);
    }
}

function repaginate(tableId) {
    showPage(tableId, paginatedTables[tableId]?.currentPage || 1);
}

// --- DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination for tables
    initPagination('rulesTable', 5);
    initPagination('materialsTable', 5);
    initPagination('optionsTable', 5);
    initPagination('stockManagementTable', 20);

    // Add form submit event listeners
    document.getElementById('editRuleForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', '#rules_section', 'editRuleModal');
    });

    document.getElementById('editMaterialForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', '#materials_section', 'editMaterialModal');
    });

    document.getElementById('editOptionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitModalForm(this, 'admin_ajax_data_handler.php', '#options_section', 'editOptionModal');
    });

    // Stock edit form submission
    const editStockForm = document.getElementById('editStockForm');
    if (editStockForm) {
        editStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitModalForm(this, 'admin_ajax_data_handler.php', '#stock_section', 'editStockModal');
        });
    }

// เพิ่มการจัดการ Toggle แสดง/ซ่อนในเครื่องคำนวณราคา
// admin.js
document.querySelectorAll('.rule-display-toggle, .material-display-toggle, .option-display-toggle').forEach(toggleInput => {
    toggleInput.addEventListener('change', function() {
        let type = '';
        let id = 0; // กำหนดค่าเริ่มต้น

        // ตรวจสอบ class เพื่อหา type และดึง id จาก data attribute ที่ถูกต้อง
        if (this.classList.contains('rule-display-toggle')) {
            type = 'rule';
            id = this.dataset.ruleId;
        } else if (this.classList.contains('material-display-toggle')) {
            type = 'material';
            id = this.dataset.materialId;
        } else if (this.classList.contains('option-display-toggle')) {
            type = 'option';
            id = this.dataset.optionId;
        }

        if (type && id) { // ตรวจสอบว่าหา type และ id เจอ
            const isVisible = this.checked;

            fetch('admin_ajax_data_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_display_status&type=${type}&id=${id}&visible=${isVisible ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`${type} ID ${id} display status updated to ${isVisible}`);
                    // คุณอาจต้องการแสดง Global Message ที่นี่ด้วย
                    // displayGlobalMessage('success', `${type} "${data.name || id}" display status updated.`);
                } else {
                    console.error('Error updating display status:', data.error);
                    displayGlobalMessage('error', `Error updating display status: ${data.error || 'Unknown error'}`);
                    this.checked = !isVisible; // คืนค่า checkbox ถ้าเกิดข้อผิดพลาด
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                displayGlobalMessage('error', 'Fetch error: Could not connect to server.');
                this.checked = !isVisible; // คืนค่า checkbox ถ้าเกิดข้อผิดพลาด
            });
        } else {
            console.error('Could not determine type or id for toggle:', this);
            displayGlobalMessage('error', 'Error: Could not determine item type or ID for toggle.');
        }
    });
});       

// ฟังก์ชันสำหรับจัดการการอัปเดตสต็อก
function handleStockUpdate(stockId, buttonElement) {
    const row = buttonElement.closest('tr');
    const input = row.querySelector('.stock-quantity-input');
    const newQuantity = input.value;

    const formData = new FormData();
    formData.append('action', 'update_stock');
    formData.append('stock_id', stockId);
    formData.append('quantity', newQuantity);

    fetch('admin_ajax_data_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.setAttribute('data-initial-value', newQuantity);
                row.classList.remove('changed');
                displayGlobalMessage('success', data.message);
            } else {
                displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาดในการอัปเดตสต็อก');
            }
        })
        .catch(error => console.error('Error updating stock:', error));
}
window.handleStockUpdate = handleStockUpdate;
// ฟังก์ชันสำหรับตั้งค่าการตรวจจับการเปลี่ยนแปลงในตารางสต็อก
function setupStockControls() {
    const stockTable = document.getElementById('stockManagementTable');
    if (!stockTable) return;

    stockTable.querySelectorAll('tbody tr').forEach(row => {
        const input = row.querySelector('.stock-quantity-input');
        if (input) {
            input.addEventListener('input', () => {
                if (input.value !== input.getAttribute('data-initial-value')) {
                    row.classList.add('changed');
                } else {
                    row.classList.remove('changed');
                }
            });
        }
    });
}

    // Configuration for which columns to search in each table
    const tableSearchConfigs = {
        'rulesTable': { individualInputId: 'ruleSearch', searchCols: [1, 3] }, // Column indices: ชื่อกฎ, หน่วย
        'materialsTable': { individualInputId: 'materialSearch', searchCols: [1, 2, 4] }, // ประเภท, ชื่อวัสดุ, หน่วย
        'optionsTable': { individualInputId: 'optionSearch', searchCols: [1, 3] }, // ชื่อออปชัน, หมวดหมู่
        'stockManagementTable': { individualInputId: 'stockSearch', searchCols: [1, 2, 4] } // ค้นหาจาก ประเภท, ชื่อสินค้า, หน่วย (index ของ td เริ่มจาก 0)
    };

    /**
     * Applies filters to all configured tables based on global and individual search terms.
     */
    function applyCombinedFilters() {
        const globalSearchTerm = document.getElementById('globalSearch').value.toUpperCase();

        for (const tableId in tableSearchConfigs) {
            const config = tableSearchConfigs[tableId];
            let individualSearchTerm = '';
            if (config.individualInputId) {
                const individualInput = document.getElementById(config.individualInputId);
                if (individualInput) {
                    individualSearchTerm = individualInput.value.toUpperCase();
                }
            }            

            const state = paginatedTables[tableId];
            if (!state) {
                console.warn(`Pagination state not found for table: ${tableId}. Skipping filter.`);
                continue;
            }

            const newFilteredRows = [];
            for (const row of state.originalRows) {
                // Skip "no data" rows or invalid rows
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                    continue; // This is the "no data" row, don't include it in filtered data rows
                }
                if (config.searchCols.length > 0 && row.cells.length <= Math.max(...config.searchCols)) {
                    continue; // Skip rows that don't have the required columns
                }

                let matchesIndividual = !individualSearchTerm;
                let matchesGlobal = !globalSearchTerm;

                if (individualSearchTerm) {
                    matchesIndividual = false;
                    for (const colIndex of config.searchCols) {
                        if (row.cells[colIndex]) {
                            const txtValue = row.cells[colIndex].textContent || row.cells[colIndex].innerText;
                            if (txtValue.toUpperCase().indexOf(individualSearchTerm) > -1) {
                                matchesIndividual = true;
                                break;
                            }
                        }
                    }
                }

                if (globalSearchTerm) {
                    matchesGlobal = false;
                    for (const colIndex of config.searchCols) {
                        if (row.cells[colIndex]) {
                            const txtValue = row.cells[colIndex].textContent || row.cells[colIndex].innerText;
                            if (txtValue.toUpperCase().indexOf(globalSearchTerm) > -1) {
                                matchesGlobal = true;
                                break;
                            }
                        }
                    }
                }

                if (matchesIndividual && matchesGlobal) {
                    newFilteredRows.push(row);
                }
            }
            state.filteredRows = newFilteredRows;
            showPage(tableId, 1);
            updateNoDataRowVisibility(tableId);
        }
    }

    /**
     * Shows or hides the "no data" row for a table based on visible data rows.
     * @param {string} tableId - The ID of the table.
     */
    function updateNoDataRowVisibility(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.getElementsByTagName("tbody")[0];
        if (!tbody) return;
    
        const state = paginatedTables[tableId];
        const hasVisibleDataRows = state && state.filteredRows && state.filteredRows.length > 0;
    
        let noDataRowElement = null;
        // Find the "no data" row. It should be in originalRows if it exists.
        if (state && state.originalRows) {
            for (const row of state.originalRows) {
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                    noDataRowElement = row;
                    break;
                }
            }
        }
        if (!noDataRowElement) { // Fallback: try to find it in the current tbody
            const allRowsInTbody = tbody.getElementsByTagName("tr");
            for (let i = 0; i < allRowsInTbody.length; i++) {
                if (allRowsInTbody[i].cells.length === 1 && allRowsInTbody[i].cells[0].colSpan > 1) {
                    noDataRowElement = allRowsInTbody[i];
                    break;
                }
            }
        }
        if (noDataRowElement) {
            noDataRowElement.style.display = hasVisibleDataRows ? "none" : "";
        }
    }

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
    }

    // Setup stock controls
    setupStockControls();

    // Initial filter application on page load to handle any pre-filled values or set initial "no data" states
    applyCombinedFilters();

    // --- (ส่วนที่เหลือของ admin.js เช่น modal handling, delete, edit, stock update) ---
    // Ensure that existing functions like openEditModal, handleDeleteClick, handleStockUpdate
    // are still present and working. This new code focuses on filtering.
});
