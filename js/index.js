// js/index.js (เวอร์ชันล่าสุด)

// --- ตัวแปรสำหรับเก็บผลลัพธ์ล่าสุด ---
let currentResults = {
    sticker: null, // เพิ่มสำหรับสติ๊กเกอร์
    letter: null, // เพิ่มสำหรับตัวอักษร
    lightbox: null, // เพิ่มสำหรับกล่องไฟ
    vinyl: null // เพิ่มสำหรับผ้าไวนิล
};

function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function (match) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match];
    });
}

function formatNumber(num) {
    try {
        let parsedNum = parseFloat(num);
        if (isNaN(parsedNum)) return '0.00';
        return parsedNum.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch(e) { return '0.00'; }
}

function fallbackCopyTextToClipboard(text, buttonElement) {
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

function showCopyFeedback(buttonElement) {
    const originalText = buttonElement.textContent;
    buttonElement.textContent = 'คัดลอกแล้ว!';
    buttonElement.classList.add('copied');
    setTimeout(() => {
        buttonElement.textContent = originalText;
        buttonElement.classList.remove('copied');
    }, 1500);
}

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
    } else if (type === 'vinyl') { // เพิ่มสำหรับผ้าไวนิล
        text += `ประเภท: ผ้าไวนิล\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
        text += `จำนวน: ${r.quantity} ผืน\n`;
        if (r.vinyl_material_name) {
            text += `ชนิดผ้า: ${htmlspecialchars(r.vinyl_material_name)}\n`;
        }
    }

    text += optionsText;
    if (r.travel_price > 0) {
        text += `ค่าเดินทาง: ${formatNumber(r.travel_price)} บาท (${htmlspecialchars(r.travel_desc)})\n`;
    }
    text += `--------------------\n`;
    text += `ราคารวมโดยประมาณ: ${formatNumber(r.total_price)} บาท`;

    return text;
}

function clearForm(formId, resultId) {
    const form = document.getElementById(formId);
    const resultDiv = document.getElementById(resultId);
    if (form) {
        form.reset();
        const travelSelect = form.querySelector('select[id$="_travel_type"]'); // ใช้ $ เพื่อจับคู่ travel_type ที่ลงท้าย
        if(travelSelect) travelSelect.dispatchEvent(new Event('change'));

        // เคลียร์ค่า input ที่อาจจะไม่ reset ด้วย form.reset() (ถ้ามี)
        const distanceInput = form.querySelector('input[id$="_distance_km"]');
        if (distanceInput) distanceInput.value = '';

    }
    if (resultDiv) {
        resultDiv.innerHTML = '';
        resultDiv.className = '';
    }
    const calculatorType = formId.replace('Form','').toLowerCase();
    currentResults[calculatorType] = null; // เคลียร์ข้อมูลที่เก็บไว้ด้วย
}

function displayResults(calculatorType, data, error) {
    let resultDivId = '';
    let resultClass = '';
    let title = '';

    if (calculatorType === 'sticker') { resultDivId = 'sticker_result'; resultClass = 'sticker'; title = 'สติ๊กเกอร์'; }
    else if (calculatorType === 'letter') { resultDivId = 'letter_result'; resultClass = 'letter'; title = 'ตัวอักษร'; }
    else if (calculatorType === 'lightbox') { resultDivId = 'lightbox_result'; resultClass = 'lightbox'; title = 'กล่องไฟ'; }
    else if (calculatorType === 'vinyl') { resultDivId = 'vinyl_result'; resultClass = 'vinyl'; title = 'ผ้าไวนิล'; } // เพิ่ม
    else { return; }

    const resultDiv = document.getElementById(resultDivId);
    if (!resultDiv) return;
    resultDiv.innerHTML = '';
    resultDiv.className = '';
    currentResults[calculatorType] = null;

    if (error) {
        resultDiv.innerHTML = `<p class="error">${htmlspecialchars(error)}</p>`;
        resultDiv.className = 'result-details';
        return;
    }

    if (data && data.success && data.results) {
        const r = data.results;
        currentResults[calculatorType] = r;
        resultDiv.className = `result-details ${resultClass}`; // เพิ่ม class ตามประเภท
        let html = `<h3>รายละเอียดราคา (${htmlspecialchars(title)}):</h3><ul style="list-style: none; padding: 0;">`;

        if(calculatorType === 'sticker'){
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li>พื้นที่คำนวณ: <strong>${formatNumber(r.area)}</strong> ตร.ม.</li>`;
            html += `<li>จำนวน: <strong>${r.quantity}</strong> แผ่น</li>`;
            html += `<li>ราคาต่อแผ่น: <strong class="main-item-price">${formatNumber(r.price_per_sheet)}</strong> บาท</li>`;
            html += `<li class="sub-item-detail">ค่าสติ๊กเกอร์: <span class="sub-item-price">${formatNumber(r.sticker_price)} บาท</span></li>`;
            if (r.sheet_price > 0) {
                html += `<li class="sub-item-detail">ค่าวัสดุแผ่น (${htmlspecialchars(r.sheet_name)}): <span class="sub-item-price">${formatNumber(r.sheet_price)} บาท</span></li>`;
            }
        } else if(calculatorType === 'letter'){
            html += `<li>ข้อมูล: <strong>${htmlspecialchars(String(r.quantity))}</strong> ตัว, สูง <strong>${htmlspecialchars(String(r.height))}</strong> นิ้ว</li>`;
            html += `<li>วัสดุ: <strong>${htmlspecialchars(r.material_name)}</strong> (${formatNumber(r.material_price_pu)} ${htmlspecialchars(r.material_unit)})</li>`;
            html += `<li>ค่าวัสดุ: <strong class="main-item-price">${formatNumber(r.base_price)}</strong> บาท</li>`;
        } else if(calculatorType === 'lightbox'){
            html += `<li>รูปทรง: <strong>${htmlspecialchars(r.shape)}</strong> (${htmlspecialchars(r.type_name)})</li>`;
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li>พื้นที่คำนวณ: <strong>${formatNumber(r.area)}</strong> ตร.ม.</li>`;
            html += `<li>ค่ากล่องไฟ: <strong class="main-item-price">${formatNumber(r.base_price)}</strong> บาท</li>`;
        } else if(calculatorType === 'vinyl'){
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li>พื้นที่คำนวณ: <strong>${formatNumber(r.area)}</strong> ตร.ม.</li>`;
            html += `<li>จำนวน: <strong>${r.quantity}</strong> ผืน</li>`;
            if (r.vinyl_material_name) {
                 html += `<li>ชนิดผ้า: <strong>${htmlspecialchars(r.vinyl_material_name)}</strong> (${formatNumber(r.vinyl_material_price_sqm)} บาท/ตร.ม.)</li>`;
            }
            html += `<li>ค่าผ้าไวนิล: <strong class="main-item-price">${formatNumber(r.vinyl_base_price)}</strong> บาท</li>`;
        }

        if (r.options && r.options.length > 0) {
            html += '<li>ค่าออปชันเสริม: <ul style="list-style: none; padding-left: 20px;">';
            let optTotal = 0; r.options.forEach(opt => { html += `<li>- ${htmlspecialchars(opt.name)}: ${formatNumber(opt.price)} บาท</li>`; optTotal += parseFloat(opt.price); });
            html += `<li>&nbsp;&nbsp;<strong>รวมค่าออปชัน: ${formatNumber(optTotal)} บาท</strong></li></ul></li>`;
        }
        if (r.travel_price > 0) { html += `<li>ค่าเดินทาง (${htmlspecialchars(r.travel_desc)}): <strong>${formatNumber(r.travel_price)}</strong> บาท</li>`; }
        if (data.warning && data.warning !== "") { html += `<li><em style="color:orange;">คำแนะนำ: ${htmlspecialchars(data.warning)}</em></li>`;}

        const copyButton = `<button type="button" class="btn-action btn-copy" data-type="${calculatorType}">คัดลอก</button>`;
        const totalPrice = parseFloat(r.total_price);

        let quantityUnit = 'ชิ้น';
        if (calculatorType === 'sticker') quantityUnit = 'แผ่น';
        else if (calculatorType === 'vinyl') quantityUnit = 'ผืน';
        else if (calculatorType === 'letter') quantityUnit = 'ตัว';


        if (['sticker', 'vinyl'].includes(calculatorType)) {
             html += `<li class="total-price"><strong>ราคารวมโดยประมาณ (${r.quantity} ${quantityUnit}):</strong><strong class="price-value">${formatNumber(totalPrice)} บาท</strong> ${copyButton}</li>`;
        } else if (calculatorType === 'letter' && r.quantity > 1) {
             html += `<li class="total-price"><strong>ราคารวมโดยประมาณ (${r.quantity} ${quantityUnit}):</strong><strong class="price-value">${formatNumber(totalPrice)} บาท</strong> ${copyButton}</li>`;
        }
         else {
            html += `<li class="total-price"><strong>ราคารวมโดยประมาณ:</strong><strong class="price-value">${formatNumber(totalPrice)} บาท</strong> ${copyButton}</li>`;
        }
        html += '</ul>';
        resultDiv.innerHTML = html;
    } else if (data && data.error) {
         if (data.error !== "กรุณากรอก กว้าง x สูง ให้ถูกต้อง" &&
            data.error !== "กรุณากรอก ความสูง (>0), จำนวน (>0) และเลือกวัสดุ" &&
            data.error !== "กรุณากรอก กว้าง (>0), ยาว (>0) และเลือกประเภทกล่องไฟ" &&
            data.error !== "กรุณากรอก กว้าง x สูง ให้ถูกต้อง (และเลือกชนิดผ้า ถ้ามี)" && // เพิ่มสำหรับ vinyl
            data.error !== "ขนาดไม่ถูกต้อง (ต้อง > 0)" ) {
                resultDiv.innerHTML = `<p class="error">${htmlspecialchars(data.error)}</p>`;
                resultDiv.className = 'result-details';
        }
    }
}

function handleFormCalculation(formId, calculatorType) {
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
    } else if (calculatorType === 'vinyl') { // เพิ่ม
         if(!formData.get('vn_width') || !formData.get('vn_height') /* || !formData.get('vn_material_type') */ || parseFloat(formData.get('vn_width')) <= 0 || parseFloat(formData.get('vn_height')) <= 0) requiredFieldsFilled = false;
    }

    const resultDivId = calculatorType === 'sticker' ? 'sticker_result' :
                        (calculatorType === 'letter' ? 'letter_result' :
                        (calculatorType === 'lightbox' ? 'lightbox_result' :
                        (calculatorType === 'vinyl' ? 'vinyl_result' : ''))); // เพิ่ม

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

function setupTravelDropdown(selectId, sectionId, formIdToRecalculate, calcTypeToRecalculate) {
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
    ['stickerForm', 'letterForm', 'lightboxForm', 'vinylForm'].forEach(formId => { // เพิ่ม 'vinylForm'
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

    setupTravelDropdown('st_travel_type', 'st_distance_section', 'stickerForm', 'sticker');
    setupTravelDropdown('travel_type', 'lt_distance_section', 'letterForm', 'letter');
    setupTravelDropdown('lb_travel_type', 'lb_distance_section', 'lightboxForm', 'lightbox');
    setupTravelDropdown('vn_travel_type', 'vn_distance_section', 'vinylForm', 'vinyl'); // เพิ่ม

    document.getElementById('st_distance_km')?.addEventListener('keyup', () => handleFormCalculation('stickerForm', 'sticker'));
    document.getElementById('distance_km')?.addEventListener('keyup', () => handleFormCalculation('letterForm', 'letter'));
    document.getElementById('lb_distance_km')?.addEventListener('keyup', () => handleFormCalculation('lightboxForm', 'lightbox'));
    document.getElementById('vn_distance_km')?.addEventListener('keyup', () => handleFormCalculation('vinylForm', 'vinyl')); // เพิ่ม

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