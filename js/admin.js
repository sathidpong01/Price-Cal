// js/admin.js (เวอร์ชันล่าสุด)

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
    // console.log("[DEBUG] Updating table row:", type, data);
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
        }
        cell.textContent = htmlspecialchars(newValue);
    });

    row.style.transition = 'background-color 0.2s ease-out';
    row.style.backgroundColor = '#d4edda'; // สีเขียวอ่อน (Success)
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
    .then(response => {
        if (!response.ok) { // ตรวจสอบ HTTP status code ก่อน
            return response.text().then(text => { throw new Error(`Server error: ${response.status} - ${text}`) });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displayGlobalMessage('success', data.message);
            const rowId = `${type}-row-${id}`;
            const row = document.getElementById(rowId);
            
            if (row) {
                const table = row.closest('table');
                const tableId = table ? table.id : null;

                row.style.transition = 'background-color 0.3s ease-out, opacity 0.5s ease-in 0.3s';
                row.style.backgroundColor = '#f8d7da'; // สีแดงอ่อน

                setTimeout(() => {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        if (tableId) {
                            const state = paginatedTables[tableId];
                            if (state) {
                                state.originalRows = state.originalRows.filter(r => r.id !== rowId);
                            }
                        }
                        row.remove();
                        if (tableId) {
                            repaginate(tableId);
                        }
                    }, 500); 
                }, 300); 
            }
        } else {
            displayGlobalMessage('error', data.error || 'เกิดข้อผิดพลาดในการลบ');
        }
    })
    .catch(error => {
        console.error('Error during AJAX delete:', error);
        displayGlobalMessage('error', 'เกิดข้อผิดพลาด: ' + error.message);
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "none";
}

function openEditModal(type, id) {
    // console.log(`[DEBUG] 1. Attempting to open modal for type: ${type}, id: ${id}`);
    let modalId = ''; let formId = '';
    let url = `admin_ajax_data_handler.php?action=get_${type}_data&id=${id}`;

    if (type === 'rule') { modalId = 'editRuleModal'; formId = 'editRuleForm'; }
    else if (type === 'material') { modalId = 'editMaterialModal'; formId = 'editMaterialForm'; }
    else if (type === 'option') { modalId = 'editOptionModal'; formId = 'editOptionForm'; }
    else { console.error('[DEBUG] Unknown modal type:', type); return; }

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    if (!modal || !form) { console.error('[DEBUG] Modal or Form not found! Modal ID:', modalId, 'Form ID:', formId); return; }

    // console.log(`[DEBUG] 2. Fetching data from: ${url}`);
    fetch(url)
        .then(response => {
            // console.log('[DEBUG] 3. Fetch response status:', response.status);
            if (!response.ok) { throw new Error('Network response was not ok ' + response.statusText); }
            return response.json();
        })
        .then(data => {
            // console.log('[DEBUG] 4. Fetch data received:', data);
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
                }
                // console.log(`[DEBUG] 5. Setting modal display to "block" for:`, modalId);
                modal.style.display = "block";
                const firstInput = form.querySelector('input[type="text"], input[type="number"], select');
                if (firstInput) firstInput.focus();
            } else {
                // console.error('[DEBUG] 5b. Fetch successful but data error:', data.error);
                displayGlobalMessage('error', 'ไม่สามารถดึงข้อมูลได้: ' + (data.error || 'ไม่ทราบสาเหตุ'));
            }
        })
        .catch(error => {
            // console.error('[DEBUG] 6. Error fetching data for modal:', error);
            displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' + error.message);
        });
}

function submitModalForm(formElement, url, sectionToReload, modalId) {
    const formData = new FormData(formElement);
    const type = modalId.replace('edit', '').replace('Modal', '').toLowerCase();
    fetch(url, { method: 'POST', body: formData })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(`Server error: ${response.status} - ${text}`) });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal(modalId);
            displayGlobalMessage('success', data.message || 'บันทึกข้อมูลเรียบร้อย!');
            if (data.data) {
                updateTableRow(type, data.data);
            } else {
                console.warn("No data returned after update, page might need manual refresh if table structure depends on it.");
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
    messageElement.innerHTML = text; // text is already htmlspecialchars from PHP or safe string from JS
    messagesDiv.innerHTML = '';
    messagesDiv.appendChild(messageElement);
    if (type === 'success' || !modalIdToKeepOpen) {
         setTimeout(() => {
             if(messageElement && messagesDiv.contains(messageElement)) messageElement.style.display = 'none';
         }, 5000);
    }
}

let paginatedTables = {};

function initPagination(tableId, rowsPerPage) {
    // console.log(`[DEBUG] Initializing pagination for ${tableId}`);
    const table = document.getElementById(tableId);
    if (!table) { /*console.error("Table not found:", tableId);*/ return; }
    const tbody = table.getElementsByTagName('tbody')[0];
    if (!tbody) { /*console.error("Tbody not found:", tableId);*/ return; }

    const allRowsInTbody = Array.from(tbody.getElementsByTagName('tr'));

    paginatedTables[tableId] = {
        originalRows: allRowsInTbody.filter(row => row.getElementsByTagName('td').length > 1), // Filter out "no data" rows
        rowsPerPage: parseInt(rowsPerPage, 10) || 5,
        currentPage: 1,
        paginationControls: null
    };
    repaginate(tableId);
}

function showPage(tableId, page) {
    const state = paginatedTables[tableId];
    if (!state || !state.originalRows) return;

    state.currentPage = page;
    const visibleRows = state.originalRows.filter(row => !row.classList.contains('filtered-out-by-search'));

    state.originalRows.forEach(row => {
        if (!row.classList.contains('filtered-out-by-search')) {
             row.style.display = 'none';
        }
    });

    const start = (page - 1) * state.rowsPerPage;
    const end = start + state.rowsPerPage;

    visibleRows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = "";
        }
    });
    updatePaginationControls(tableId);
}

function updatePaginationControls(tableId) {
    const state = paginatedTables[tableId];
    if (!state || !state.originalRows) return;

    if (!state.paginationControls) {
         let paginationDiv = document.getElementById(tableId + 'PaginationContainer');
         if (!paginationDiv) {
             paginationDiv = document.createElement("div");
             paginationDiv.className = "pagination";
             paginationDiv.id = tableId + 'PaginationContainer';
             const tableElement = document.getElementById(tableId); // Renamed to avoid conflict with 'table' variable from outer scope
             if (tableElement && tableElement.parentNode.classList.contains('table-responsive')) {
                 tableElement.parentNode.insertAdjacentElement('afterend', paginationDiv);
             } else if (tableElement) {
                 tableElement.insertAdjacentElement('afterend', paginationDiv);
             }
         }
         state.paginationControls = paginationDiv;
    }

    const visibleRows = state.originalRows.filter(row => !row.classList.contains('filtered-out-by-search'));
    const totalPages = Math.ceil(visibleRows.length / state.rowsPerPage);

    state.paginationControls.innerHTML = '';

    if (totalPages <= 1 && visibleRows.length <= state.rowsPerPage) { // Hide if only one page and not exceeding rows per page
        state.paginationControls.style.display = 'none';
        return;
    }
    state.paginationControls.style.display = 'flex';

    let prevBtn = document.createElement("button");
    prevBtn.innerHTML = "&laquo; ก่อนหน้า";
    prevBtn.type = "button";
    prevBtn.disabled = state.currentPage === 1;
    prevBtn.onclick = function() { if (state.currentPage > 1) { showPage(tableId, state.currentPage - 1); } };
    state.paginationControls.appendChild(prevBtn);

    const maxPageButtons = 5; let startPage, endPage;
    if (totalPages <= maxPageButtons) { startPage = 1; endPage = totalPages; }
    else {
        let sideButtons = Math.floor((maxPageButtons - 3) / 2); // e.g., 1 for 5 buttons total (1 ... current ... 1)
        if (state.currentPage <= sideButtons + 1) { // current is near the start
            startPage = 1; endPage = maxPageButtons -1; // Show 1, 2, 3, ..., n
        } else if (state.currentPage >= totalPages - sideButtons) { // current is near the end
            startPage = totalPages - maxPageButtons + 2; endPage = totalPages; // Show 1, ..., n-2, n-1, n
        } else { // current is in the middle
            startPage = state.currentPage - sideButtons; endPage = state.currentPage + sideButtons; // Show 1, ..., x-1, x, x+1, ..., n
        }
    }

    if (startPage > 1) {
        let firstPageBtn = document.createElement("button"); firstPageBtn.innerHTML = 1; firstPageBtn.type = "button"; firstPageBtn.onclick = function() { showPage(tableId, 1); }; state.paginationControls.appendChild(firstPageBtn);
        if (startPage > 2) { let ellipsis = document.createElement("span"); ellipsis.innerHTML = "&hellip;"; ellipsis.style.padding = "8px 12px"; state.paginationControls.appendChild(ellipsis); }
    }
    for (let i = startPage; i <= endPage; i++) {
        if (i > totalPages) continue; // Make sure not to create buttons beyond totalPages
        let pageBtn = document.createElement("button"); pageBtn.innerHTML = i; pageBtn.type = "button";
        if (i === state.currentPage) { pageBtn.classList.add("active"); }
        pageBtn.onclick = function() { showPage(tableId, i); }; state.paginationControls.appendChild(pageBtn);
    }
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) { let ellipsis = document.createElement("span"); ellipsis.innerHTML = "&hellip;"; ellipsis.style.padding = "8px 12px"; state.paginationControls.appendChild(ellipsis); }
        let lastPageBtn = document.createElement("button"); lastPageBtn.innerHTML = totalPages; lastPageBtn.type = "button"; lastPageBtn.onclick = function() { showPage(tableId, totalPages); }; state.paginationControls.appendChild(lastPageBtn);
    }

    let nextBtn = document.createElement("button"); nextBtn.innerHTML = "ถัดไป &raquo;"; nextBtn.type = "button";
    nextBtn.disabled = state.currentPage === totalPages || totalPages === 0;
    nextBtn.onclick = function() { if (state.currentPage < totalPages) { showPage(tableId, state.currentPage + 1); } };
    state.paginationControls.appendChild(nextBtn);
}


function repaginate(tableId) {
    const state = paginatedTables[tableId];
    if (!state || !state.originalRows) { /* console.log("Repaginate called, but no state or originalRows for", tableId); */ return; }
    
    // Re-filter originalRows from DOM in case rows were added by PHP (not AJAX) then JS loaded
    // This is less ideal than fully managing rows via JS originalRows
    const table = document.getElementById(tableId);
    if (table) {
        const tbody = table.getElementsByTagName('tbody')[0];
        if (tbody) {
            const currentDomRows = Array.from(tbody.getElementsByTagName('tr'));
            // A simple check, could be more robust. If counts differ significantly, might re-init.
            if (currentDomRows.filter(row => row.getElementsByTagName('td').length > 1).length !== state.originalRows.length && state.originalRows.length === 0) {
                 // console.log(`Repaginating ${tableId}: DOM rows differ from state, re-initializing originalRows.`);
                 state.originalRows = currentDomRows.filter(row => row.getElementsByTagName('td').length > 1);
            }
        }
    }
    filterTable(document.getElementById(tableId.replace('Table','Search'))?.id || `${tableId}Search`, tableId, ...(tableId === 'materialsTable' ? [1,2] : [1]));
    showPage(tableId, state.currentPage > Math.ceil(state.originalRows.filter(row => !row.classList.contains('filtered-out-by-search')).length / state.rowsPerPage) ? 1 : state.currentPage);
}

function filterTable(inputId, tableId, ...columnIndices) {
    const inputElement = document.getElementById(inputId); // Renamed to avoid conflict
    if (!inputElement) { /* console.log("Filter input not found:", inputId); */ return; }
    let filter = inputElement.value.toUpperCase();

    const state = paginatedTables[tableId];
    if(!state || !state.originalRows) { /* console.error(`Pagination not initialized for table ${tableId}`); */ return; }

    state.originalRows.forEach(row => {
        let found = false;
        if (row.getElementsByTagName("td").length === 0 || row.textContent.trim().includes("ยังไม่มีข้อมูล")) {
             row.style.display = 'none'; row.classList.add('filtered-out-by-search'); return;
        }
        for (let colIndex of columnIndices) {
            let td = row.getElementsByTagName("td")[colIndex];
            if (td) { let txtValue = td.textContent || td.innerText; if (txtValue.toUpperCase().indexOf(filter) > -1) { found = true; break; } }
        }
        if (found) { row.style.display = ''; row.classList.remove('filtered-out-by-search'); } // Display will be handled by showPage
        else { row.style.display = 'none'; row.classList.add('filtered-out-by-search'); }
    });
    // After filtering, reset to page 1 and update controls
    state.currentPage = 1;
    showPage(tableId, 1);
}

document.addEventListener('DOMContentLoaded', function() {
    // console.log("[DEBUG] DOM Loaded. Setting up listeners.");

    document.getElementById('editRuleForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'rules_section', 'editRuleModal'); });
    document.getElementById('editMaterialForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'materials_section', 'editMaterialModal'); });
    document.getElementById('editOptionForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'options_section', 'editOptionModal'); });

    window.onclick = function(event) {
        document.querySelectorAll('.modal').forEach(modal => {
             if (event.target == modal) {
                modal.style.display = "none";
            }
        });
    }
     window.onkeydown = function(event) {
        if (event.key === "Escape") {
             document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = "none";
            });
        }
    };

    const rowsPerPageRules = 5;
    const rowsPerPageMaterials = 10; // Changed from 5 to 10 as per admin_backup.php
    const rowsPerPageOptions = 5;

    if (document.getElementById('rulesTable')) initPagination('rulesTable', rowsPerPageRules);
    if (document.getElementById('materialsTable')) initPagination('materialsTable', rowsPerPageMaterials);
    if (document.getElementById('optionsTable')) initPagination('optionsTable', rowsPerPageOptions);

    document.getElementById('ruleSearch')?.addEventListener('keyup', (event) => filterTable(event.target.id, 'rulesTable', 1));
    document.getElementById('materialSearch')?.addEventListener('keyup', (event) => filterTable(event.target.id, 'materialsTable', 1, 2));
    document.getElementById('optionSearch')?.addEventListener('keyup', (event) => filterTable(event.target.id, 'optionsTable', 1));

     setTimeout(() => {
        const globalMessages = document.getElementById('globalMessages');
        if(globalMessages) {
            const successMsg = globalMessages.querySelector('.message.success');
            if (successMsg) successMsg.style.display = 'none';
        }
    }, 5000);
});