// js/index.js (Optimized with AppConfig)

/**
 * Application Configuration & Constants
 * รวมค่าคงที่และการตั้งค่าต่างๆ ไว้ที่เดียวเพื่อให้แก้ไขง่าย
 */
const AppConfig = {
    debounceDelay: 400,
    letterCalculation: {
        widthRatio: 0.8, // อัตราส่วนความกว้างต่อความสูง
        inchToCm: 2.54   // 1 นิ้ว = 2.54 ซม.
    },
    thaiVowels: "่้๊๋ัิีึืุู็์ะ", // สระและวรรณยุกต์ไทยที่นับเป็น 0.5 ตัวอักษร
    selectors: {
        forms: ["stickerForm", "letterForm", "lightboxForm", "vinylForm"],
        results: {
            sticker: "sticker_result",
            letter: "letter_result",
            lightbox: "lightbox_result",
            vinyl: "vinyl_result"
        }
    }
};

/**
 * Debounce Function
 * ลดการเรียกฟังก์ชันซ้ำๆ ขณะพิมพ์
 */
function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}

/**
 * Helper: Count Characters for Pricing
 * นับจำนวนตัวอักษร (สระ/วรรณยุกต์ไทยนับ 0.5)
 */
function countCharacters(text) {
    if (!text) return 0;
    const cleanedText = text.trim().replace(/\s+/g, " ");
    if (!cleanedText) return 0;
    
    let totalCount = 0;
    for (let char of cleanedText) {
        if (char === " ") continue;
        if (AppConfig.thaiVowels.includes(char)) {
            totalCount += 0.5;
        } else {
            totalCount += 1;
        }
    }
    return totalCount;
}

/**
 * Helper: HTML Special Chars
 * ป้องกัน XSS
 */
function htmlspecialchars(str) {
    if (typeof str !== "string") return "";
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return str.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Helper: Format Number
 * จัดรูปแบบตัวเลขเป็นทศนิยม 2 ตำแหน่ง
 */
function formatNumber(num) {
    const n = parseFloat(num);
    return isNaN(n) ? "0.00" : n.toLocaleString("th-TH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// เก็บผลลัพธ์ล่าสุดเพื่อใช้ในการ Copy
let currentResults = {
    sticker: null,
    letter: null,
    lightbox: null,
    vinyl: null,
};

/**
 * Display Results
 * แสดงผลลัพธ์การคำนวณในหน้าเว็บ
 */
function displayResults(calculatorType, data, error) {
    const resultDivId = `${calculatorType}_result`;
    const resultDiv = document.getElementById(resultDivId);
    if (!resultDiv) return;

    resultDiv.innerHTML = "";
    resultDiv.className = "";
    currentResults[calculatorType] = null;

    if (error) {
        resultDiv.innerHTML = `<p class="error">${htmlspecialchars(error)}</p>`;
        resultDiv.className = "result-details";
        return;
    }

    if (data && data.success && data.results) {
        const r = data.results;
        currentResults[calculatorType] = r;
        resultDiv.className = `result-details ${calculatorType}`;
        
        const titles = {
            sticker: 'สติ๊กเกอร์',
            letter: 'ตัวอักษร',
            lightbox: 'กล่องไฟ',
            vinyl: 'ผ้าไวนิล'
        };
        const title = titles[calculatorType] || '';

        let html = `<h3>รายละเอียดราคา (${htmlspecialchars(title)}):</h3><ul style="list-style: none; padding: 0;">`;

        // --- Logic การแสดงผลแยกตามประเภท ---
        if (calculatorType === "sticker") {
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li>วัสดุ: <strong>${htmlspecialchars(r.sticker_material_name)}</strong></li>`;
            html += `<li class="sub-item-detail">ค่าสติ๊กเกอร์: <span class="sub-item-price">${formatNumber(r.sticker_price)} บาท</span></li>`;
            if (r.sheet_price > 0) {
                html += `<li class="sub-item-detail">ค่าวัสดุแผ่น (${htmlspecialchars(r.sheet_name)}): <span class="sub-item-price">${formatNumber(r.sheet_price)} บาท</span></li>`;
            }
        } else if (calculatorType === "letter") {
            // ใช้ค่าคงที่จาก AppConfig ในการคำนวณขนาดประมาณการ
            const text = document.getElementById('letter_text').value;
            const heightInches = parseFloat(r.height);
            const charCount = countCharacters(text);
            
            const widthInches = (charCount * heightInches * AppConfig.letterCalculation.widthRatio).toFixed(1);
            const widthCm = (widthInches * AppConfig.letterCalculation.inchToCm).toFixed(1);
            const heightCm = (heightInches * AppConfig.letterCalculation.inchToCm).toFixed(1);
            
            html += `<li>ข้อมูล: <strong>${htmlspecialchars(String(r.quantity))}</strong> ตัว</li>`;
            html += `<li>ขนาดโดยประมาณ: <strong>${widthCm} x ${heightCm}</strong> ซม.</li>`;
            html += `<li>วัสดุ: <strong>${htmlspecialchars(r.material_name)}</strong> (${formatNumber(r.material_price_pu)} ${htmlspecialchars(r.material_unit)})</li>`;
            html += `<li class="sub-item-detail">ค่าวัสดุ: <span class="sub-item-price">${formatNumber(r.base_price)} บาท</span></li>`;

        } else if (calculatorType === "lightbox") {
            html += `<li>รูปทรง: <strong>${htmlspecialchars(r.shape)}</strong> (${htmlspecialchars(r.type_name)})</li>`;
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            html += `<li class="sub-item-detail">ค่ากล่องไฟ: <span class="sub-item-price">${formatNumber(r.base_price)} บาท</span></li>`;

        } else if (calculatorType === "vinyl") {
            html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
            if (r.vinyl_material_name) {
                html += `<li>ชนิดผ้า: <strong>${htmlspecialchars(r.vinyl_material_name)}</strong></li>`;
            }
            html += `<li class="sub-item-detail">ค่าผ้าไวนิล: <span class="sub-item-price">${formatNumber(r.vinyl_base_price)} บาท</span></li>`;
        }

        // --- แสดง Options เสริม ---
        if (r.options && r.options.length > 0) {
            let optHtml = '<li>ค่าออปชันเสริม: <ul style="list-style: none; padding-left: 20px;">';
            r.options.forEach(opt => {
                optHtml += `<li>- ${htmlspecialchars(opt.name)}: ${formatNumber(opt.price)} บาท</li>`;
            });
            optHtml += `</ul></li>`;
            html += optHtml;
        }

        // --- แสดงค่าเดินทาง ---
        if (r.travel_price > 0) {
            html += `<li>ค่าเดินทาง (${htmlspecialchars(r.travel_desc)}): <strong>${formatNumber(r.travel_price)}</strong> บาท</li>`;
        }

        // --- ปุ่ม Copy และราคารวม ---
        const copyButton = `<button type="button" class="btn-action btn-copy" data-type="${calculatorType}">คัดลอก</button>`;
        html += `<li class="total-price"><strong>ราคารวมโดยประมาณ:</strong><strong class="price-value">${formatNumber(r.total_price)} บาท</strong> ${copyButton}</li>`;
        html += "</ul>";
        
        resultDiv.innerHTML = html;

    } else if (data && data.error) {
        // ซ่อน Error ถ้าเป็นแค่คำเตือน "กรุณากรอก..."
        if (data.error.includes("กรุณา")) {
            resultDiv.innerHTML = "";
        } else {
            resultDiv.innerHTML = `<p class="error">${htmlspecialchars(data.error)}</p>`;
            resultDiv.className = "result-details";
        }
    }
}

/**
 * Handle Form Calculation
 * ส่งข้อมูลไปยัง Server เพื่อคำนวณ
 */
function handleFormCalculation(formId, calculatorType) {
    const form = document.getElementById(formId);
    if (!form) return;

    const formData = new FormData(form);
    formData.append("calculator_type", calculatorType);

    const resultDivId = `${calculatorType}_result`;
    const resultDiv = document.getElementById(resultDivId);
    
    // Simple Client-side Validation
    let requiredFieldsFilled = true;
    if (calculatorType === 'sticker') {
        if (!formData.get('st_width') || !formData.get('st_height') || !formData.get('st_material_type')) requiredFieldsFilled = false;
    } else if (calculatorType === 'letter') {
        const qty = parseFloat(formData.get('letter_quantity'));
        if (!formData.get('letter_height') || !formData.get('letter_quantity') || !formData.get('material') || qty <= 0) requiredFieldsFilled = false;
    }
    
    if (!requiredFieldsFilled) {
        if(resultDiv) resultDiv.innerHTML = "";
        return;
    }

    if (resultDiv) {
        resultDiv.innerHTML = '<div class="spinner-container"><div class="spinner"></div><p>กำลังคำนวณ...</p></div>';
        resultDiv.className = "result-details";
    }

    fetch("calculate_ajax.php", { method: "POST", body: formData })
        .then(response => response.json())
        .then(data => { displayResults(calculatorType, data, null); })
        .catch(error => {
            console.error("Error:", error);
            displayResults(calculatorType, null, "เกิดข้อผิดพลาดในการเชื่อมต่อ");
        });
}

/**
 * Build Copy Text
 * สร้างข้อความสำหรับ Copy to Clipboard
 */
function buildCopyText(type) {
    const r = currentResults[type];
    if (!r) return "ไม่มีข้อมูลสำหรับคัดลอก";

    let text = "";
    let optionsText = "";

    if (r.options && r.options.length > 0) {
        optionsText += "ออปชันเสริม:\n";
        r.options.forEach((opt) => {
            optionsText += `- ${htmlspecialchars(opt.name)} (${formatNumber(opt.price)} บาท)\n`;
        });
    }

    if (type === "sticker") {
        text += `ประเภท: สติ๊กเกอร์\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
        text += `จำนวน: ${r.quantity} แผ่น\n`;
        if (r.sheet_name) text += `วัสดุแผ่น: ${htmlspecialchars(r.sheet_name)}\n`;
    } else if (type === "letter") {
        text += `ประเภท: ตัวอักษร\n`;
        text += `ขนาด: สูง ${htmlspecialchars(String(r.height))} นิ้ว\n`;
        text += `จำนวน: ${r.quantity} ตัว\n`;
        text += `วัสดุ: ${htmlspecialchars(r.material_name)}\n`;
    } else if (type === "lightbox") {
        text += `ประเภท: กล่องไฟ (${htmlspecialchars(r.type_name)})\n`;
        text += `รูปทรง: ${htmlspecialchars(r.shape)}\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
    } else if (type === "vinyl") {
        text += `ประเภท: ผ้าไวนิล\n`;
        text += `ขนาด: ${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))} ซม.\n`;
        text += `จำนวน: ${r.quantity} ผืน\n`;
        if (r.vinyl_material_name) text += `ชนิดผ้า: ${htmlspecialchars(r.vinyl_material_name)}\n`;
    }

    text += optionsText;
    if (r.travel_price > 0) {
        text += `ค่าเดินทาง: ${formatNumber(r.travel_price)} บาท (${htmlspecialchars(r.travel_desc)})\n`;
    }
    text += `--------------------\n`;
    text += `ราคารวมโดยประมาณ: ${formatNumber(r.total_price)} บาท`;

    return text;
}

/**
 * Clipboard Functions
 */
function fallbackCopyTextToClipboard(text, buttonElement) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed"; // Avoid scrolling to bottom
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand("copy");
        if (successful) showCopyFeedback(buttonElement);
    } catch (err) {
        console.error("Fallback: Error during copy", err);
    }
    document.body.removeChild(textArea);
}

function showCopyFeedback(buttonElement) {
    const originalText = buttonElement.textContent;
    buttonElement.textContent = "คัดลอกแล้ว!";
    buttonElement.classList.add("copied");
    setTimeout(() => {
        buttonElement.textContent = originalText;
        buttonElement.classList.remove("copied");
    }, 1500);
}

function copyTextToClipboard(text, buttonElement) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback(buttonElement);
        }).catch(() => {
            fallbackCopyTextToClipboard(text, buttonElement);
        });
    } else {
        fallbackCopyTextToClipboard(text, buttonElement);
    }
}

/**
 * DOM Content Loaded - Event Bindings
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Bind Calculation Events
    const debouncedCalculation = debounce(handleFormCalculation, AppConfig.debounceDelay);

    AppConfig.selectors.forms.forEach((formId) => {
        const form = document.getElementById(formId);
        if (form) {
            const calculatorType = formId.replace("Form", "").toLowerCase();

            // Input Text/Number -> Debounce
            form.querySelectorAll('input[type="text"], input[type="number"]').forEach((element) => {
                element.addEventListener("keyup", () => debouncedCalculation(formId, calculatorType));
            });

            // Select/Checkbox/Radio -> Instant
            form.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach((element) => {
                element.addEventListener("change", () => handleFormCalculation(formId, calculatorType));
            });
        }
    });

    // 2. Setup Travel Dropdowns
    const travelConfigs = [
        { select: "st_travel_type", section: "st_distance_section" },
        { select: "vn_travel_type", section: "vn_distance_section" },
        { select: "travel_type", section: "lt_distance_section" },
        { select: "lb_travel_type", section: "lb_distance_section" }
    ];
    travelConfigs.forEach(cfg => {
        const select = document.getElementById(cfg.select);
        const section = document.getElementById(cfg.section);
        if (select && section) {
            select.addEventListener("change", function() {
                section.style.display = this.value === 'out_city' ? 'block' : 'none';
            });
        }
    });

    // 3. Setup Letter Form Specifics
    if (document.getElementById('letterForm')) {
        const inputTypeText = document.getElementById("letter_input_type_text");
        const inputTypeCount = document.getElementById("letter_input_type_count");
        const letterTextInputGroup = document.getElementById("letter_text_input_group");
        const letterTextarea = document.getElementById("letter_text");
        const letterQuantityInput = document.getElementById("letter_quantity");

        function updateQuantityMethod() {
            if (inputTypeText.checked) {
                letterTextInputGroup.style.display = "";
                letterQuantityInput.readOnly = true;
                letterTextarea.dispatchEvent(new Event('input'));
            } else {
                letterTextInputGroup.style.display = "none";
                letterQuantityInput.readOnly = false;
            }
        }
        
        inputTypeText.addEventListener("change", updateQuantityMethod);
        inputTypeCount.addEventListener("change", updateQuantityMethod);
        letterTextarea.addEventListener("input", function() {
            if (inputTypeText.checked) {
                letterQuantityInput.value = countCharacters(this.value);
            }
        });
        
        updateQuantityMethod();
    }

    // 4. Stock Table Filtering
    function filterIndexStockTable() {
        const searchInput = document.getElementById("indexStockSearch");
        const typeFilter = document.getElementById("indexStockTypeFilter");
        const table = document.getElementById("stockDisplayTable");
        
        if (!searchInput || !typeFilter || !table) return;

        const searchTerm = searchInput.value.toUpperCase();
        const selectedType = typeFilter.value.toUpperCase();
        const tbody = table.querySelector("tbody");
        const rows = tbody.querySelectorAll("tr");
        let hasVisibleRows = false;

        let noDataRow = tbody.querySelector(".no-data-row");
        if (noDataRow) noDataRow.style.display = "none";

        rows.forEach(row => {
            if (row.classList.contains('no-data-row') || row.cells.length < 4) return;

            const typeText = row.cells[0].textContent.toUpperCase();
            const nameText = row.cells[1].textContent.toUpperCase();

            const typeMatch = (selectedType === "" || typeText === selectedType);
            const searchMatch = (searchTerm === "" || nameText.includes(searchTerm));

            if (typeMatch && searchMatch) {
                row.style.display = "";
                hasVisibleRows = true;
            } else {
                row.style.display = "none";
            }
        });

        if (!hasVisibleRows) {
            if (!noDataRow) {
                noDataRow = tbody.insertRow();
                noDataRow.classList.add('no-data-row');
                const cell = noDataRow.insertCell();
                cell.colSpan = 4;
                cell.textContent = "ไม่มีข้อมูลที่ตรงกับเงื่อนไข";
                cell.style.textAlign = "center";
                cell.style.padding = "15px";
                cell.style.color = "#777";
            }
            noDataRow.style.display = "";
        }
    }

    const stockSearchInput = document.getElementById("indexStockSearch");
    const stockTypeFilter = document.getElementById("indexStockTypeFilter");

    if (stockSearchInput) {
        stockSearchInput.addEventListener("keyup", debounce(filterIndexStockTable, 300));
    }
    if (stockTypeFilter) {
        stockTypeFilter.addEventListener("change", filterIndexStockTable);
    }

    // 5. Copy Button Delegation
    const container = document.querySelector(".container");
    if (container) {
        container.addEventListener("click", function(event) {
            if (event.target.classList.contains("btn-copy")) {
                const button = event.target;
                const type = button.getAttribute("data-type");
                if (type && currentResults[type]) {
                    copyTextToClipboard(buildCopyText(type), button);
                }
            }
        });
    }
});