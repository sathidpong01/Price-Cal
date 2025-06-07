<?php
// เรียกใช้ไฟล์เชื่อมต่อและไฟล์ดึงข้อมูล
include 'includes/db_connect.php';
include 'includes/data_fetcher.php';

// --- ใช้ฟังก์ชันจาก data_fetcher.php เพื่อดึงข้อมูล ---
$price_rules = getPriceRules($conn);
$options_by_category = getAllOptionsByCategory($conn);
$all_materials = getAllMaterialsByType($conn);

// --- กำหนดค่าตัวแปรจากข้อมูลที่ดึงมา ---
$sticker_price_per_sqm = isset($price_rules['ราคาสติ๊กเกอร์ต่อตรม.']) ? $price_rules['ราคาสติ๊กเกอร์ต่อตรม.'] : 450;
$travel_cost_per_km = isset($price_rules['ติดตั้งนอกเมือง']) ? $price_rules['ติดตั้งนอกเมือง'] : 10;
$travel_cost_in_city = isset($price_rules['ติดตั้งในเมือง']) ? $price_rules['ติดตั้งในเมือง'] : 300;

// --- แยกข้อมูล Materials และ Options สำหรับแต่ละฟอร์ม ---
$materials_list_for_letter = $all_materials['ตัวอักษรโลหะ'];
$lightbox_list_for_form = $all_materials['กล่องไฟ'];
$sheet_list_for_sticker = $all_materials['วัสดุแผ่น'];
$vinyl_materials_list = $all_materials['ผ้าไวนิล'];
$sticker_materials_list = $all_materials['สติ๊กเกอร์'];

$sticker_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['สติ๊กเกอร์']);
$vinyl_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['ผ้าไวนิล']);
$letter_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['ตัวอักษรโลหะ']);
$lightbox_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['กล่องไฟ']);

// --- ดึงข้อมูลสต็อกสำหรับแสดงผล (เฉพาะหน้านี้) ---
$stock_list_display = [];
$sql_stock_display = "SELECT product_name, product_type, quantity, unit FROM stock WHERE quantity > 0 ORDER BY product_type, product_name";
$result_stock_display = $conn->query($sql_stock_display);
if ($result_stock_display) {
    while ($row_stock = $result_stock_display->fetch_assoc()) {
        $stock_list_display[] = $row_stock;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรแกรมคำนวณราคาสินค้า</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="/icon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/icon/favicon.svg" />
    <link rel="shortcut icon" href="/icon/favicon.ico" />
</head>

<body>
    <div class="container">
        <nav class="navbar">
            <div class="navbar-container">
                <a href="index.php" class="navbar-brand">โปรแกรมคำนวณราคา</a>
                <a href="admin.php" class="navbar-button">ไปหน้าจัดการ (Admin)</a>
            </div>
        </nav>

        <div class="main-content-area">
            <div class="main-layout-grid">
                <div class="calculators-grid">

                    <div class="calculator-section">
                        <h1>คำนวณราคาสติ๊กเกอร์</h1> <br>
                        <form id="stickerForm">
                            <div><label for="st_width">ความกว้าง (ซม.):</label><input type="text" id="st_width"
                                    name="st_width" placeholder="กรอกความกว้าง" value=""></div>
                            <div><label for="st_height">ความสูง (ซม.):</label><input type="text" id="st_height"
                                    name="st_height" placeholder="กรอกความสูง" value=""></div>
                            <div><label for="st_quantity">จำนวน (แผ่น):</label><input type="text" id="st_quantity"
                                    name="st_quantity" placeholder="กรอกจำนวน" value="1"></div>
                            <?php if (!empty($sticker_materials_list)): ?>
                            <div><label for="st_material_type">ชนิดสติ๊กเกอร์:</label>
                                <select id="st_material_type" name="st_material_type" required>
                                    <option value="">-- กรุณาเลือกชนิดสติ๊กเกอร์ --</option>
                                    <?php foreach ($sticker_materials_list as $st_mat): ?>
                                    <option value="<?php echo $st_mat['material_id']; ?>">
                                        <?php echo htmlspecialchars($st_mat['material_name']) . " (" . number_format($st_mat['price_per_unit'], 2) . " บาท/ตร.ม.)"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div><label for="st_sheet_material">วัสดุแผ่น (เสริม):</label>
                                <select id="st_sheet_material" name="st_sheet_material">
                                    <option value="none">-- ไม่ใช้วัสดุแผ่น --</option>
                                    <?php foreach ($sheet_list_for_sticker as $sh): ?>
                                    <option value="<?php echo $sh['material_id']; ?>">
                                        <?php echo htmlspecialchars($sh['material_name']) . " (" . number_format($sh['price_per_unit'], 2) . " บาท/ตร.ม.)"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="full-width-field">
                                <label>ออปชันเสริม:</label>
                                <div class="options-grid"> <?php foreach ($sticker_options as $opt): ?>
                                    <div class="option-item"> <input type="checkbox"
                                            id="st_option_<?php echo $opt['option_id']; ?>" name="st_options[]"
                                            value="<?php echo $opt['option_id']; ?>">
                                        <label for="st_option_<?php echo $opt['option_id']; ?>">
                                            <?php echo htmlspecialchars($opt['option_name']) . " (" . number_format($opt['option_price'], 2) . " บาท)"; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="full-width-field">
                                <label for="st_travel_type">ค่าเดินทาง:</label>
                                <select id="st_travel_type" name="st_travel_type">
                                    <option value="none">ไม่รวมค่าเดินทาง</option>
                                    <option value="in_city">ในเมือง
                                        (<?php echo number_format($travel_cost_in_city, 2); ?>
                                        บาท)
                                    </option>
                                    <option value="out_city">นอกเมือง
                                        (<?php echo number_format($travel_cost_per_km, 2); ?>
                                        บาท/กม.)</option>
                                </select>
                            </div>
                            <div id="st_distance_section" style="display: none;" class="full-width-field"><label
                                    for="st_distance_km">ระยะทาง (กม.):</label><input type="text" id="st_distance_km"
                                    name="st_distance_km" value=""></div>
                        </form>
                        <div class="form-actions">
                            <button type="button" class="btn-action btn-clear"
                                onclick="clearForm('stickerForm', 'sticker_result')">ล้างข้อมูล</button>
                        </div>
                        <div id="sticker_result"></div>
                    </div>

                    <div class="calculator-section">
                        <h1>คำนวณราคาผ้าไวนิล</h1>
                        <form id="vinylForm">
                            <div><label for="vn_width">ความกว้าง (ซม.):</label><input type="text" id="vn_width"
                                    name="vn_width" placeholder="กรอกความกว้าง" value=""></div>
                            <div><label for="vn_height">ความสูง (ซม.):</label><input type="text" id="vn_height"
                                    name="vn_height" placeholder="กรอกความสูง" value=""></div>
                            <div><label for="vn_quantity">จำนวน (ผืน):</label><input type="text" id="vn_quantity"
                                    name="vn_quantity" value="1"></div>

                            <?php if (!empty($vinyl_materials_list)): ?>
                            <div><label for="vn_material_type">ชนิดผ้าไวนิล:</label>
                                <select id="vn_material_type" name="vn_material_type">
                                    <option value="">-- กรุณาเลือกชนิดผ้า --</option>
                                    <?php foreach ($vinyl_materials_list as $vn_mat): ?>
                                    <option value="<?php echo $vn_mat['material_id']; ?>"
                                        data-price="<?php echo $vn_mat['price_per_unit']; ?>">
                                        <?php echo htmlspecialchars($vn_mat['material_name']) . " (" . number_format($vn_mat['price_per_unit'], 2) . " บาท/ตร.ม.)"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="full-width-field">
                                <label>ออปชันเสริม:</label>
                                <div class="options-grid">
                                    <?php foreach ($vinyl_options as $opt): ?>
                                    <div class="option-item">
                                        <input type="checkbox" id="vn_opt_<?php echo $opt['option_id']; ?>"
                                            name="vn_options[]" value="<?php echo $opt['option_id']; ?>">
                                        <label for="vn_opt_<?php echo $opt['option_id']; ?>">
                                            <?php echo htmlspecialchars($opt['option_name']) . " (" . number_format($opt['option_price'], 2) . " บาท)"; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="full-width-field"><label for="vn_travel_type">ค่าเดินทาง:</label>
                                <select id="vn_travel_type" name="vn_travel_type">
                                    <option value="none">ไม่รวมค่าเดินทาง</option>
                                    <option value="in_city">ในเมือง
                                        (<?php echo number_format($travel_cost_in_city, 2); ?>
                                        บาท)
                                    </option>
                                    <option value="out_city">นอกเมือง
                                        (<?php echo number_format($travel_cost_per_km, 2); ?>
                                        บาท/กม.)</option>
                                </select>
                            </div>
                            <div id="vn_distance_section" style="display: none;" class="full-width-field">
                                <label for="vn_distance_km">ระยะทาง (กม.):</label>
                                <input type="text" id="vn_distance_km" name="vn_distance_km" value="">
                            </div>
                        </form>
                        <div class="form-actions">
                            <button type="button" class="btn-action btn-clear"
                                onclick="clearForm('vinylForm', 'vinyl_result')">ล้างข้อมูล</button>
                        </div>
                        <div id="vinyl_result"></div>
                    </div>

                    <div class="calculator-section">
                        <h1>คำนวณราคาตัวอักษร</h1>
                        <form id="letterForm">
                            <div>
                                <label>วิธีระบุจำนวน:</label>
                                <div class="input-method-options">
                                    <span>
                                        <input type="radio" id="letter_input_type_text" name="letter_input_type"
                                            value="text" checked>
                                        <label for="letter_input_type_text">กรอกข้อความ</label>
                                    </span>
                                    <span>
                                        <input type="radio" id="letter_input_type_count" name="letter_input_type"
                                            value="count">
                                        <label for="letter_input_type_count">กรอกจำนวนเอง</label>
                                    </span>
                                </div>
                            </div>

                            <div id="letter_text_input_group">
                                <label for="letter_text">ข้อความ:</label>
                                <textarea id="letter_text" name="letter_text" rows="3"
                                    placeholder="ป้อนข้อความที่นี่เพื่อนับจำนวนตัวอักษร..."></textarea>
                            </div>

                            <div><label for="letter_height">ความสูง (นิ้ว):</label><input type="text" id="letter_height"
                                    name="letter_height" value=""></div>
                            <div><label for="letter_quantity">จำนวนตัวอักษร:</label><input type="text"
                                    id="letter_quantity" name="letter_quantity" value="" readonly></div>
                            <div><label for="material">เลือกวัสดุ:</label>
                                <select id="material" name="material">
                                    <option value="">-- กรุณาเลือก --</option>
                                    <?php foreach ($materials_list_for_letter as $mat): ?>
                                    <option value="<?php echo $mat['material_id']; ?>">
                                        <?php echo htmlspecialchars($mat['material_name']) . " (" . number_format($mat['price_per_unit'], 2) . " " . htmlspecialchars($mat['unit']) . ")"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="full-width-field">
                                <label>ออปชันเสริม:</label>
                                <div class="options-grid">
                                    <?php foreach ($letter_options as $opt): ?>
                                    <div class="option-item">
                                        <input type="checkbox" id="lt_opt_<?php echo $opt['option_id']; ?>"
                                            name="lt_options[]" value="<?php echo $opt['option_id']; ?>"> <label
                                            for="lt_opt_<?php echo $opt['option_id']; ?>">
                                            <?php echo htmlspecialchars($opt['option_name']) . " (" . number_format($opt['option_price'], 2) . " บาท)"; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="full-width-field"><label for="travel_type">ค่าเดินทาง:</label>
                                <select id="travel_type" name="travel_type">
                                    <option value="none">ไม่รวมค่าเดินทาง</option>
                                    <option value="in_city">ในเมือง
                                        (<?php echo number_format($travel_cost_in_city, 2); ?>
                                        บาท)
                                    </option>
                                    <option value="out_city">นอกเมือง
                                        (<?php echo number_format($travel_cost_per_km, 2); ?>
                                        บาท/กม.)</option>
                                </select>
                            </div>
                            <div id="lt_distance_section" style="display: none;" class="full-width-field"><label
                                    for="distance_km">ระยะทาง (กม.):</label><input type="text" id="distance_km"
                                    name="distance_km" value=""></div>
                        </form>
                        <div class="form-actions">
                            <button type="button" class="btn-action btn-clear"
                                onclick="clearForm('letterForm', 'letter_result')">ล้างข้อมูล</button>
                        </div>
                        <div id="letter_result"></div>
                    </div>

                    <div class="calculator-section">
                        <h1>คำนวณราคากล่องไฟ</h1>
                        <form id="lightboxForm">
                            <div><label for="lightbox_type">ประเภทกล่องไฟ:</label>
                                <select id="lightbox_type" name="lightbox_type">
                                    <option value="">-- กรุณาเลือก --</option>
                                    <?php foreach ($lightbox_list_for_form as $lb): ?>
                                    <option value="<?php echo $lb['material_id']; ?>">
                                        <?php echo htmlspecialchars($lb['material_name']) . " (" . number_format($lb['price_per_unit'], 2) . " บาท/ตร.ม.)"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label for="lb_width">ความกว้าง (ซม.):</label><input type="text" id="lb_width"
                                    name="lb_width" placeholder="กรอกความกว้าง" value=""></div>
                            <div><label for="lb_height">ความยาว/สูง (ซม.):</label><input type="text" id="lb_height"
                                    name="lb_height" placeholder="กรอกความยาว/สูง" value=""></div>
                            <div class="full-width-field">
                                <label>ออปชันเสริม:</label>
                                <div class="options-grid">
                                    <?php foreach ($lightbox_options as $opt): ?>
                                    <div class="option-item">
                                        <input type="checkbox" id="lb_opt_<?php echo $opt['option_id']; ?>"
                                            name="lb_options[]" value="<?php echo $opt['option_id']; ?>">
                                        <label for="lb_opt_<?php echo $opt['option_id']; ?>">
                                            <?php echo htmlspecialchars($opt['option_name']) . " (" . number_format($opt['option_price'], 2) . " บาท)"; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="full-width-field"><label for="lb_travel_type">ค่าเดินทาง:</label>
                                <select id="lb_travel_type" name="lb_travel_type">
                                    <option value="none">ไม่รวมค่าเดินทาง</option>
                                    <option value="in_city">ในเมือง
                                        (<?php echo number_format($travel_cost_in_city, 2); ?>
                                        บาท)
                                    </option>
                                    <option value="out_city">นอกเมือง
                                        (<?php echo number_format($travel_cost_per_km, 2); ?>
                                        บาท/กม.)</option>
                                </select>
                            </div>
                            <div id="lb_distance_section" style="display: none;" class="full-width-field"><label
                                    for="lb_distance_km">ระยะทาง (กม.):</label><input type="text" id="lb_distance_km"
                                    name="lb_distance_km" value=""></div>
                        </form>
                        <div class="form-actions">
                            <button type="button" class="btn-action btn-clear"
                                onclick="clearForm('lightboxForm', 'lightbox_result')">ล้างข้อมูล</button>
                        </div>
                        <div id="lightbox_result"></div>
                    </div>
                </div>

                <div class="stock-display-section">
                    <h1>สต็อกสินค้า</h1>
                    <div class="filter-section">
                        <div class="search-section" >
                            <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                <g><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"></path></g>
                            </svg>
                            <input type="text" id="indexStockSearch" class="search-box" placeholder="ค้นหาชื่อสินค้า...">
                        </div>
                        <div>
                            <select id="indexStockTypeFilter" class="form-control">
                                <option value="">หมวดหมู่ทั้งหมด</option>
                                <?php
                                $product_types_in_stock = [];
                                if (!empty($stock_list_display)) {
                                    foreach ($stock_list_display as $item) {
                                        $product_types_in_stock[] = $item['product_type'];
                                    }
                                }
                                $unique_product_types = array_unique($product_types_in_stock);
                                sort($unique_product_types);
                                foreach ($unique_product_types as $type):
                                ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="stockDisplayTable">
                            <thead>
                                <tr>
                                    <th>ประเภทสินค้า</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>จำนวน</th>
                                    <th>หน่วย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock_list_display)): ?>
                                <tr>
                                    <td colspan="4">ไม่มีข้อมูลสินค้าในสต็อก</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($stock_list_display as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div> <!-- END OF main-content-area -->
    </div>

    <script src="js/index.js" defer></script>
</body>

</html>