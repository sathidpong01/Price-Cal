// js/index.js (เวอร์ชันปรับปรุง Copy ให้มีรายละเอียด)

// --- ตัวแปรสำหรับเก็บผลลัพธ์ล่าสุด ---
let currentResults = {
    sticker: null,
    letter: null,
    lightbox: null
};

function htmlspecialchars(str) { /* ... เหมือนเดิม ... */
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function (match) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match];
    });
}

function formatNumber(num) { /* ... เหมือนเดิม ... */
    try {
        let parsedNum = parseFloat(num);
        if (isNaN(parsedNum)) return '0.00';
        return parsedNum.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch(e) { return '0.00'; }
}

function fallbackCopyTextToClipboard(text, buttonElement) { /* ... เหมือนเดิม ... */
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(buttonElement);
        } else {
            console.error('Fallback: Unable to copy');
            alert('ไม่สามารถคัดลอกราคาได้ (Fallback)');
        }
    } catch (err) {
        console.error('Fallback: Error during copy', err);
        alert('เกิดข้อผิดพลาดในการคัดลอก (Fallback)');
    }
    document.body.removeChild(textArea);
}

function showCopyFeedback(buttonElement) { /* ... เหมือนเดิม ... */
    const originalText = buttonElement.textContent;
    buttonElement.textContent = 'คัดลอกแล้ว!';
    buttonElement.classList.add('copied');
    setTimeout(() => {
        buttonElement.textContent = originalText;
        buttonElement.classList.remove('copied');
    }, 1500);
}

// --- ฟังก์ชันสำหรับคัดลอกข้อความ (ใช้ navigator ก่อน) ---
function copyTextToClipboard(text, buttonElement) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback(buttonElement);
        }).catch(err => {
            console.error('Clipboard API failed, trying fallback:', err);
            fallbackCopyTextToClipboard(text, buttonElement);
        });
    } else {
        console.warn('Clipboard API not available or not secure, using fallback.');
        fallbackCopyTextToClipboard(text, buttonElement);
    }
}

// --- ฟังก์ชันสร้างข้อความสำหรับคัดลอก ---
function buildCopyText(type) {
    const r = currentResults[type];
    if (!r) return "ไม่มีข้อมูลสำหรับคัดลอก";

    let text = "";
    let optionsText = "";

    if (r.options && r.options.length > 0) {
        optionsText += "ออปชันเสริม:\n";
        r.options.forEach(opt => {
            optionsText += `- ${htmlspecialchars(opt.name)} (${formatNumber(opt.price)} บาท)\n`;
        });
    }

    if (type === 'sticker') {
        text += `ประเภท: สติ๊กเกอร์\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
        text += `จำนวน: ${r.quantity} แผ่น\n`;
        if (r.sheet_name) {
            text += `วัสดุแผ่น: ${htmlspecialchars(r.sheet_name)}\n`;
        }
    } else if (type === 'letter') {
        text += `ประเภท: ตัวอักษร\n`;
        text += `ขนาด: สูง ${htmlspecialchars(String(r.height))} นิ้ว\n`;
        text += `จำนวน: ${r.quantity} ตัว\n`;
        text += `วัสดุ: ${htmlspecialchars(r.material_name)}\n`;
    } else if (type === 'lightbox') {
        text += `ประเภท: กล่องไฟ (${htmlspecialchars(r.type_name)})\n`;
        text += `รูปทรง: ${htmlspecialchars(r.shape)}\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
    }

    text += optionsText; // เพิ่มออปชัน (ถ้ามี)
    if (r.travel_price > 0) {
        text += `ค่าเดินทาง: ${formatNumber(r.travel_price)} บาท (${htmlspecialchars(r.travel_desc)})\n`;
    }
    text += `--------------------\n`;
    text += `ราคารวมโดยประมาณ: ${formatNumber(r.total_price)} บาท`;

    return text;
}


function clearForm(formId, resultId) { /* ... เหมือนเดิม ... */
    const form = document.getElementById(formId);
    const resultDiv = document.getElementById(resultId);
    if (form) {
        form.reset();
        const travelSelect = form.querySelector('select[id$="_travel_type"]');
        if(travelSelect) travelSelect.dispatchEvent(new Event('change'));
    }
    if (resultDiv) {
        resultDiv.innerHTML = '';
        resultDiv.className = '';
    }
}

// ฟังก์ชันแสดงผลลัพธ์ (แก้ไข: เก็บข้อมูล, เปลี่ยนปุ่ม Copy)
function displayResults(calculatorType, data, error) {
    let resultDivId = '';
    let resultClass = '';
    let title = '';

    if (calculatorType === 'sticker') { resultDivId = 'sticker_result'; resultClass = 'sticker'; title = 'สติ๊กเกอร์'; }
    else if (calculatorType === 'letter') { resultDivId = 'letter_result'; resultClass = 'letter'; title = 'ตัวอักษร'; }
    else if (calculatorType === 'lightbox') { resultDivId = 'lightbox_result'; resultClass = 'lightbox'; title = 'กล่องไฟ'; }
    else { return; }

    const resultDiv = document.getElementById(resultDivId);
    if (!resultDiv) return;
    resultDiv.innerHTML = '';
    resultDiv.className = '';
    currentResults[calculatorType] = null; // เคลียร์ข้อมูลเก่าก่อน

    if (error) {
        resultDiv.innerHTML = `<p class="error">${htmlspecialchars(error)}</p>`;
        resultDiv.className = 'result-details';
        return;
    }

    if (data && data.success && data.results) {
        const r = data.results;
        currentResults[calculatorType] = r; // --- เก็บข้อมูลล่าสุด ---
        resultDiv.className = `result-details ${resultClass}`;
        let html = `<h3>รายละเอียดราคา (${htmlspecialchars(title)}):</h3><ul style="list-style: none; padding: 0;">`;

        // ... (ส่วนแสดงรายละเอียดเหมือนเดิม) ...
        if(calculatorType === 'sticker'){
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`; // แสดงขนาด
            html += `<li>พื้นที่คำนวณ: <strong>${formatNumber(r.area)}</strong> ตร.ม.</li>`;
            html += `<li>จำนวน: <strong>${r.quantity}</strong> แผ่น</li>`;
            html += `<li>ราคาต่อแผ่น: <strong>${formatNumber(r.price_per_sheet)}</strong> บาท</li>`;
            html += `<li>ค่าสติ๊กเกอร์: <strong>${formatNumber(r.sticker_price)}</strong> บาท</li>`;
            if (r.sheet_price > 0) { html += `<li>ค่าวัสดุแผ่น (${htmlspecialchars(r.sheet_name)}): <strong>${formatNumber(r.sheet_price)}</strong> บาท</li>`; }
        }
        if(calculatorType === 'letter'){
            html += `<li>ข้อมูล: <strong>${htmlspecialchars(String(r.quantity))}</strong> ตัว, สูง <strong>${htmlspecialchars(String(r.height))}</strong> นิ้ว</li>`;
            html += `<li>วัสดุ: <strong>${htmlspecialchars(r.material_name)}</strong> (${formatNumber(r.material_price_pu)} ${htmlspecialchars(r.material_unit)})</li>`;
            html += `<li>ค่าวัสดุ: <strong>${formatNumber(r.base_price)}</strong> บาท</li>`;
        }
        if(calculatorType === 'lightbox'){
            html += `<li>รูปทรง: <strong>${htmlspecialchars(r.shape)}</strong> (${htmlspecialchars(r.type_name)})</li>`;
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li>พื้นที่คำนวณ: <strong>${formatNumber(r.area)}</strong> ตร.ม.</li>`;
            html += `<li>ค่ากล่องไฟ: <strong>${formatNumber(r.base_price)}</strong> บาท</li>`;
        }
        if (r.options && r.options.length > 0) {
            html += '<li>ค่าออปชันเสริม: <ul style="list-style: none; padding-left: 20px;">';
            let optTotal = 0; r.options.forEach(opt => { html += `<li>- ${htmlspecialchars(opt.name)}: ${formatNumber(opt.price)} บาท</li>`; optTotal += parseFloat(opt.price); });
            html += `<li>&nbsp;&nbsp;<strong>รวมค่าออปชัน: ${formatNumber(optTotal)} บาท</strong></li></ul></li>`;
        }
        if (r.travel_price > 0) { html += `<li>ค่าเดินทาง (${htmlspecialchars(r.travel_desc)}): <strong>${formatNumber(r.travel_price)}</strong> บาท</li>`; }
        if (data.warning && data.warning !== "") { html += `<li><em style="color:orange;">คำแนะนำ: ${htmlspecialchars(data.warning)}</em></li>`;}

        // --- แก้ไข: เปลี่ยนปุ่ม Copy ให้ไม่มี onclick แต่มี data attribute ---
        const copyButton = `<button type="button" class="btn-action btn-copy" data-type="${calculatorType}">คัดลอก</button>`;
        const totalPrice = parseFloat(r.total_price);

        if(calculatorType === 'sticker'){
            html += `<li class="total-price"><strong>ราคารวมโดยประมาณ (${r.quantity} แผ่น):</strong><strong class="price-value">${formatNumber(totalPrice)} บาท</strong> ${copyButton}</li>`;
        } else {
            html += `<li class="total-price"><strong>ราคารวมโดยประมาณ:</strong><strong class="price-value">${formatNumber(totalPrice)} บาท</strong> ${copyButton}</li>`;
        }
        html += '</ul>';
        resultDiv.innerHTML = html;
    } else if (data && data.error) {
         // ... (ส่วนแสดง Error เหมือนเดิม) ...
         if (data.error !== "กรุณากรอก กว้าง x สูง ให้ถูกต้อง" &&
            data.error !== "กรุณากรอก ความสูง (>0), จำนวน (>0) และเลือกวัสดุ" &&
            data.error !== "กรุณากรอก กว้าง (>0), ยาว (>0) และเลือกประเภทกล่องไฟ" &&
            data.error !== "ขนาดไม่ถูกต้อง (ต้อง > 0)" ) {
                resultDiv.innerHTML = `<p class="error">${htmlspecialchars(data.error)}</p>`;
                resultDiv.className = 'result-details';
        }
    }
}

function handleFormCalculation(formId, calculatorType) { /* ... เหมือนเดิม ... */
    const form = document.getElementById(formId);
    if (!form) return;
    const formData = new FormData(form);
    formData.append('calculator_type', calculatorType);
    let requiredFieldsFilled = true;
    if(calculatorType === 'sticker'){
        if(!formData.get('st_width') || !formData.get('st_height') || parseFloat(formData.get('st_width')) <= 0 || parseFloat(formData.get('st_height')) <= 0 ) requiredFieldsFilled = false;
    } else if (calculatorType === 'letter') {
        if(!formData.get('letter_height') || !formData.get('letter_quantity') || !formData.get('material') || parseFloat(formData.get('letter_height')) <= 0 || parseFloat(formData.get('letter_quantity')) <= 0) requiredFieldsFilled = false;
    } else if (calculatorType === 'lightbox') {
         if(!formData.get('lb_width') || !formData.get('lb_height') || !formData.get('lightbox_type') || parseFloat(formData.get('lb_width')) <= 0 || parseFloat(formData.get('lb_height')) <= 0) requiredFieldsFilled = false;
    }
    const resultDivId = calculatorType === 'sticker' ? 'sticker_result' : (calculatorType === 'letter' ? 'letter_result' : 'lightbox_result');
    const resultDiv = document.getElementById(resultDivId);
    if (!requiredFieldsFilled) {
        if (resultDiv) {
            resultDiv.innerHTML = '';
            resultDiv.className = '';
        }
        return;
    }
    if(resultDiv) {
        resultDiv.innerHTML = '<div class="spinner-container"><div class="spinner"></div><p>กำลังคำนวณ...</p></div>';
        resultDiv.className = 'result-details';
    }
    fetch('calculate_ajax.php', { method: 'POST', body: formData })
    .then(response => { if (!response.ok) { throw new Error('Network error: ' + response.statusText + ' - ' + response.url); } return response.json(); })
    .then(data => { displayResults(calculatorType, data, null); })
    .catch(error => { console.error('Error:', error); displayResults(calculatorType, null, 'เกิดข้อผิดพลาดในการเชื่อมต่อ หรือการคำนวณ'); });
}

function setupTravelDropdown(selectId, sectionId, formIdToRecalculate, calcTypeToRecalculate) { /* ... เหมือนเดิม ... */
    var selectElement = document.getElementById(selectId);
    var sectionElement = document.getElementById(sectionId);
    if (selectElement && sectionElement) {
        selectElement.addEventListener('change', function() {
            sectionElement.style.display = (this.value === 'out_city') ? 'block' : 'none';
            handleFormCalculation(formIdToRecalculate, calcTypeToRecalculate);
        });
        sectionElement.style.display = (selectElement.value === 'out_city') ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ... (Event Listener ฟอร์มเหมือนเดิม) ...
    ['stickerForm', 'letterForm', 'lightboxForm'].forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const calculatorType = formId.replace('Form', '').toLowerCase();
            form.querySelectorAll('input[type="text"], select').forEach(element => {
                element.addEventListener('change', () => handleFormCalculation(formId, calculatorType));
                element.addEventListener('keyup', () => handleFormCalculation(formId, calculatorType));
            });
            form.querySelectorAll('input[type="checkbox"]').forEach(element => {
                 element.addEventListener('change', () => handleFormCalculation(formId, calculatorType));
            });
        }
    });

    // ... (Dropdown ค่าเดินทางเหมือนเดิม) ...
    setupTravelDropdown('st_travel_type', 'st_distance_section', 'stickerForm', 'sticker');
    setupTravelDropdown('travel_type', 'lt_distance_section', 'letterForm', 'letter');
    setupTravelDropdown('lb_travel_type', 'lb_distance_section', 'lightboxForm', 'lightbox');

    // ... (Listener ระยะทางเหมือนเดิม) ...
    document.getElementById('st_distance_km')?.addEventListener('keyup', () => handleFormCalculation('stickerForm', 'sticker'));
    document.getElementById('distance_km')?.addEventListener('keyup', () => handleFormCalculation('letterForm', 'letter'));
    document.getElementById('lb_distance_km')?.addEventListener('keyup', () => handleFormCalculation('lightboxForm', 'lightbox'));

    // --- เพิ่ม: Event Listener สำหรับปุ่ม Copy (ใช้ Event Delegation) ---
    document.querySelector('.container').addEventListener('click', function(event) {
        if (event.target.classList.contains('btn-copy')) {
            const button = event.target;
            const type = button.getAttribute('data-type');
            if (type && currentResults[type]) {
                const textToCopy = buildCopyText(type);
                copyTextToClipboard(textToCopy, button);
            } else {
                console.error("ไม่พบข้อมูลสำหรับคัดลอก:", type);
                alert("ไม่พบข้อมูลสำหรับคัดลอก");
            }
        }
    });
});