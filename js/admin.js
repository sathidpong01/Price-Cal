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
                    form.elements['option_category'].value = itemData.category || 'ทั่วไป'; // เพิ่มการตั้งค่า category
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
function initPagination(tableId, rowsPerPage) { /* ... โค้ดเดิม ... */ }
function showPage(tableId, page) { /* ... โค้ดเดิม ... */ }
function updatePaginationControls(tableId) { /* ... โค้ดเดิม ... */ }
function repaginate(tableId) { /* ... โค้ดเดิม ... */ }
function filterTable(inputId, tableId, ...columnIndices) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const filter = input.value.toLowerCase();
    const rows = table.getElementsByTagName('tr');

    // Loop through all table rows except header
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        let found = false;

        // Check each specified column
        for (const colIndex of columnIndices) {
            const cell = row.getElementsByTagName('td')[colIndex];
            if (cell) {
                const text = cell.textContent || cell.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        // Show/hide row based on search result
        row.style.display = found ? '' : 'none';
    }

    // Repaginate the table after filtering
    repaginate(tableId);
}

// --- DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination for tables
    initPagination('rulesTable', 10);
    initPagination('materialsTable', 10);
    initPagination('optionsTable', 10);

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

    // Add search event listeners
    document.getElementById('ruleSearch')?.addEventListener('keyup', () => filterTable('ruleSearch', 'rulesTable', 1));
    document.getElementById('materialSearch')?.addEventListener('keyup', () => filterTable('materialSearch', 'materialsTable', 1, 2));
    document.getElementById('optionSearch')?.addEventListener('keyup', () => filterTable('optionSearch', 'optionsTable', 1, 3));
});