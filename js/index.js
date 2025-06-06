// js/index.js (เวอร์ชันแก้ไขสมบูรณ์)

/**
 * Debounce Function
 * ฟังก์ชันสำหรับหน่วงเวลาการเรียกใช้ฟังก์ชันอื่น
 * @param {Function} func - ฟังก์ชันที่ต้องการหน่วงเวลา
 * @param {number} delay - เวลาที่ต้องการหน่วง (มิลลิวินาที)
 */
function debounce(func, delay) {
  let timeout;
  return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), delay);
  };
}

// --- ฟังก์ชัน Helper อื่นๆ ---
function countCharacters(text) {
  if (!text) return 0;
  const cleanedText = text.trim().replace(/\s+/g, " ");
  if (!cleanedText) return 0;
  const thaiVowelsAndTones = "่้๊๋ัิีึืุู็์ะ";
  let totalCount = 0;
  for (let i = 0; i < cleanedText.length; i++) {
      const char = cleanedText[i];
      if (char === " ") continue;
      if (thaiVowelsAndTones.includes(char)) {
          totalCount += 0.5;
      } else {
          totalCount += 1;
      }
  }
  return totalCount;
}

function htmlspecialchars(str) {
  if (typeof str !== "string") return "";
  return str.replace(/[&<>"']/g, function(match) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[match];
  });
}

function formatNumber(num) {
  try {
      let parsedNum = parseFloat(num);
      if (isNaN(parsedNum)) return "0.00";
      return parsedNum.toLocaleString("th-TH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  } catch (e) {
      return "0.00";
  }
}

// --- ตัวแปรสำหรับเก็บผลลัพธ์ล่าสุด ---
let currentResults = {
  sticker: null,
  letter: null,
  lightbox: null,
  vinyl: null,
};

// --- ฟังก์ชันหลักในการแสดงผล, คัดลอก, เคลียร์ฟอร์ม ---

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
      let title = '';
      if (calculatorType === 'sticker') title = 'สติ๊กเกอร์';
      else if (calculatorType === 'letter') title = 'ตัวอักษร';
      else if (calculatorType === 'lightbox') title = 'กล่องไฟ';
      else if (calculatorType === 'vinyl') title = 'ผ้าไวนิล';

      let html = `<h3>รายละเอียดราคา (${htmlspecialchars(title)}):</h3><ul style="list-style: none; padding: 0;">`;

      if (calculatorType === "sticker") {
          html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
          html += `<li>วัสดุ: <strong>${htmlspecialchars(r.sticker_material_name)}</strong></li>`;
          
          // --- ADDED START: เพิ่มบรรทัดที่หายไปสำหรับราคาสติ๊กเกอร์ ---
          html += `<li class="sub-item-detail">ค่าสติ๊กเกอร์: <span class="sub-item-price">${formatNumber(r.sticker_price)} บาท</span></li>`;
          // --- ADDED END ---

          if (r.sheet_price > 0) {
              html += `<li class="sub-item-detail">ค่าวัสดุแผ่น (${htmlspecialchars(r.sheet_name)}): <span class="sub-item-price">${formatNumber(r.sheet_price)} บาท</span></li>`;
          }
      } else if (calculatorType === "letter") {
          const text = document.getElementById('letter_text').value;
          const heightInches = parseFloat(r.height);
          const charCount = countCharacters(text);
          const widthInches = (charCount * heightInches * 0.8).toFixed(1);
          const widthCm = (widthInches * 2.54).toFixed(1);
          const heightCm = (heightInches * 2.54).toFixed(1);
          
          html += `<li>ข้อมูล: <strong>${htmlspecialchars(String(r.quantity))}</strong> ตัว</li>`;
          html += `<li>ขนาดโดยประมาณ: <strong>${widthCm} x ${heightCm}</strong> ซม.</li>`;
          html += `<li>วัสดุ: <strong>${htmlspecialchars(r.material_name)}</strong> (${formatNumber(r.material_price_pu)} ${htmlspecialchars(r.material_unit)})</li>`;

          // --- ADDED START: เพิ่มบรรทัดที่หายไปสำหรับราคาตัวอักษร ---
          html += `<li class="sub-item-detail">ค่าวัสดุ: <span class="sub-item-price">${formatNumber(r.base_price)} บาท</span></li>`;
          // --- ADDED END ---

      } else if (calculatorType === "lightbox") {
          html += `<li>รูปทรง: <strong>${htmlspecialchars(r.shape)}</strong> (${htmlspecialchars(r.type_name)})</li>`;
          html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;

          // --- ADDED START: เพิ่มบรรทัดที่หายไปสำหรับราคากล่องไฟ ---
           html += `<li class="sub-item-detail">ค่ากล่องไฟ: <span class="sub-item-price">${formatNumber(r.base_price)} บาท</span></li>`;
          // --- ADDED END ---

      } else if (calculatorType === "vinyl") {
          html += `<li>ขนาด: <strong>${htmlspecialchars(String(r.width))} x ${htmlspecialchars(String(r.height))}</strong> ซม.</li>`;
          if (r.vinyl_material_name) {
              html += `<li>ชนิดผ้า: <strong>${htmlspecialchars(r.vinyl_material_name)}</strong></li>`;
          }

          // --- ADDED START: เพิ่มบรรทัดที่หายไปสำหรับราคาผ้าไวนิล ---
          html += `<li class="sub-item-detail">ค่าผ้าไวนิล: <span class="sub-item-price">${formatNumber(r.vinyl_base_price)} บาท</span></li>`;
          // --- ADDED END ---
      }

      if (r.options && r.options.length > 0) {
          let optTotal = 0;
          let optHtml = '<li>ค่าออปชันเสริม: <ul style="list-style: none; padding-left: 20px;">';
          r.options.forEach(opt => {
              optHtml += `<li>- ${htmlspecialchars(opt.name)}: ${formatNumber(opt.price)} บาท</li>`;
              optTotal += parseFloat(opt.price);
          });
          optHtml += `</ul></li>`;
          html += optHtml;
      }

      if (r.travel_price > 0) {
          html += `<li>ค่าเดินทาง (${htmlspecialchars(r.travel_desc)}): <strong>${formatNumber(r.travel_price)}</strong> บาท</li>`;
      }

      const copyButton = `<button type="button" class="btn-action btn-copy" data-type="${calculatorType}">คัดลอก</button>`;
      html += `<li class="total-price"><strong>ราคารวมโดยประมาณ:</strong><strong class="price-value">${formatNumber(r.total_price)} บาท</strong> ${copyButton}</li>`;
      html += "</ul>";
      resultDiv.innerHTML = html;
  } else if (data && data.error) {
      if (data.error.includes("กรุณา")) {
          resultDiv.innerHTML = "";
      } else {
          resultDiv.innerHTML = `<p class="error">${htmlspecialchars(data.error)}</p>`;
          resultDiv.className = "result-details";
      }
  }
}

function handleFormCalculation(formId, calculatorType) {
  const form = document.getElementById(formId);
  if (!form) return;

  const formData = new FormData(form);
  formData.append("calculator_type", calculatorType);

  const resultDivId = `${calculatorType}_result`;
  const resultDiv = document.getElementById(resultDivId);
  
  // Validation ก่อนส่ง
  let requiredFieldsFilled = true;
  if (calculatorType === 'sticker') {
      if (!formData.get('st_width') || !formData.get('st_height') || !formData.get('st_material_type')) requiredFieldsFilled = false;
  } else if (calculatorType === 'letter') {
      if (!formData.get('letter_height') || !formData.get('letter_quantity') || !formData.get('material') || parseFloat(formData.get('letter_quantity')) <= 0) requiredFieldsFilled = false;
  } // ...เพิ่ม validation สำหรับฟอร์มอื่น
  
  if (!requiredFieldsFilled) {
      if(resultDiv) resultDiv.innerHTML = ""; // ล้างผลลัพธ์ถ้าข้อมูลไม่ครบ
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

function clearForm(formId, resultId) {
  const form = document.getElementById(formId);
  const resultDiv = document.getElementById(resultId);
  if (form) {
      form.reset();
      // บังคับให้ event 'change' ทำงานเพื่อซ่อน/แสดง section ระยะทาง
      const travelSelect = form.querySelector('select[id$="_travel_type"]');
      if (travelSelect) travelSelect.dispatchEvent(new Event('change'));
  }
  if (resultDiv) resultDiv.innerHTML = "";
  const calculatorType = formId.replace("Form", "").toLowerCase();
  currentResults[calculatorType] = null;
}

// ... ฟังก์ชัน copy, buildCopyText เหมือนเดิม ...
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
      if (r.sheet_name) {
          text += `วัสดุแผ่น: ${htmlspecialchars(r.sheet_name)}\n`;
      }
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
      const successful = document.execCommand("copy");
      if (successful) {
          showCopyFeedback(buttonElement);
      }
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
* เมื่อ DOM โหลดเสร็จแล้ว จะเริ่มทำการผูก Event ต่างๆ
*/
document.addEventListener("DOMContentLoaded", function() {
  
  // สร้างเวอร์ชัน debounced ของฟังก์ชันคำนวณ
  const debouncedCalculation = debounce(handleFormCalculation, 400);

  // ผูก Event Listener กับทุกฟอร์ม
  ["stickerForm", "letterForm", "lightboxForm", "vinylForm"].forEach((formId) => {
      const form = document.getElementById(formId);
      if (form) {
          const calculatorType = formId.replace("Form", "").toLowerCase();

          form.querySelectorAll('input[type="text"], input[type="number"]').forEach((element) => {
              // *** แก้ไขตรงนี้: ใช้ debounce กับ keyup ***
              element.addEventListener("keyup", () => debouncedCalculation(formId, calculatorType));
          });

          form.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach((element) => {
               // คำนวณทันทีเมื่อมีการเปลี่ยนแปลงค่าที่ไม่ใช่การพิมพ์
              element.addEventListener("change", () => handleFormCalculation(formId, calculatorType));
          });
      }
  });

  // ตั้งค่า Dropdown ค่าเดินทาง
  function setupTravelDropdown(selectId, sectionId) {
      const selectElement = document.getElementById(selectId);
      const sectionElement = document.getElementById(sectionId);
      if (selectElement && sectionElement) {
          selectElement.addEventListener("change", function() {
              sectionElement.style.display = this.value === 'out_city' ? 'block' : 'none';
          });
      }
  }
  setupTravelDropdown("st_travel_type", "st_distance_section");
  setupTravelDropdown("vn_travel_type", "vn_distance_section");
  setupTravelDropdown("travel_type", "lt_distance_section");
  setupTravelDropdown("lb_travel_type", "lb_distance_section");

  // ตั้งค่าการทำงานของฟอร์มตัวอักษรโดยเฉพาะ
  function setupLetterForm() {
      const inputTypeText = document.getElementById("letter_input_type_text");
      const inputTypeCount = document.getElementById("letter_input_type_count");
      const letterTextInputGroup = document.getElementById("letter_text_input_group");
      const letterTextarea = document.getElementById("letter_text");
      const letterQuantityInput = document.getElementById("letter_quantity");

      function updateQuantityMethod() {
          if (inputTypeText.checked) {
              letterTextInputGroup.style.display = "";
              letterQuantityInput.readOnly = true;
              letterTextarea.dispatchEvent(new Event('input')); // อัปเดตจำนวนทันที
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
      
      updateQuantityMethod(); // เรียกครั้งแรกเพื่อตั้งค่า
  }
  if (document.getElementById('letterForm')) {
      setupLetterForm();
  }


  // ผูก Event Listener กับปุ่ม "คัดลอก" ผ่าน Event Delegation
  document.querySelector(".container").addEventListener("click", function(event) {
      if (event.target.classList.contains("btn-copy")) {
          const button = event.target;
          const type = button.getAttribute("data-type");
          if (type && currentResults[type]) {
              const textToCopy = buildCopyText(type);
              copyTextToClipboard(textToCopy, button);
          }
      }
  });
});