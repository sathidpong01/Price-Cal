<?php
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include 'includes/db_connect.php';

// --- ดึงข้อมูลพื้นฐาน (Price Rules) ---
$price_rules = [];
$sql_rules = "SELECT rule_name, rule_value FROM price_rules";
$result_rules = $conn->query($sql_rules);
if ($result_rules && $result_rules->num_rows > 0) {
    while ($row_rule = $result_rules->fetch_assoc()) {
        $price_rules[$row_rule['rule_name']] = $row_rule['rule_value'];
    }
}
$sticker_price_per_sqm = isset($price_rules['ราคาสติ๊กเกอร์ต่อตรม.']) ? $price_rules['ราคาสติ๊กเกอร์ต่อตรม.'] : 450;
$travel_cost_per_km = isset($price_rules['ค่าเดินทาง']) ? $price_rules['ค่าเดินทาง'] : 10;
$travel_cost_in_city = isset($price_rules['ค่าเดินทางในเมือง']) ? $price_rules['ค่าเดินทางในเมือง'] : 300;


// --- ดึงข้อมูล Options และจัดกลุ่มตาม Category ---
$options_by_category = [
    'ทั่วไป' => [],
    'สติ๊กเกอร์' => [],
    'ผ้าไวนิล' => [],
    'ตัวอักษรโลหะ' => [],
    'กล่องไฟ' => []
];
$sql_options = "SELECT option_id, option_name, option_price, category FROM options";
$result_options = $conn->query($sql_options);
if ($result_options && $result_options->num_rows > 0) {
    while ($row_opt = $result_options->fetch_assoc()) {
        $category_key = $row_opt['category'] ?? 'ทั่วไป';
        if (array_key_exists($category_key, $options_by_category)) {
            $options_by_category[$category_key][] = $row_opt;
        } else {
            $options_by_category['ทั่วไป'][] = $row_opt;
        }
    }
}

// --- ดึงข้อมูล Materials ---
$materials_list_for_letter = [];
$lightbox_list_for_form = [];
$sheet_list_for_sticker = [];
$vinyl_materials_list = [];

$sql_materials_all = "SELECT material_id, product_type, material_name, price_per_unit, unit FROM materials";
$result_materials_all_query = $conn->query($sql_materials_all);
if ($result_materials_all_query && $result_materials_all_query->num_rows > 0) {
    while ($row_mat = $result_materials_all_query->fetch_assoc()) {
        if ($row_mat['product_type'] == 'ตัวอักษรโลหะ') {
            $materials_list_for_letter[] = $row_mat;
        } elseif ($row_mat['product_type'] == 'กล่องไฟ') {
            $lightbox_list_for_form[] = $row_mat;
        } elseif ($row_mat['product_type'] == 'วัสดุแผ่น') {
            $sheet_list_for_sticker[] = $row_mat;
        } elseif ($row_mat['product_type'] == 'ผ้าไวนิล') {
            $vinyl_materials_list[] = $row_mat;
        }
    }
}

// --- ดึงข้อมูลสต็อกสำหรับแสดงผล ---
$stock_list_display = [];
$sql_stock_display = "SELECT product_name, product_type, quantity, unit FROM stock WHERE quantity > 0 ORDER BY product_type, product_name";
$result_stock_display = $conn->query($sql_stock_display);
if ($result_stock_display) {
    while ($row_stock = $result_stock_display->fetch_assoc()) {
        $stock_list_display[] = $row_stock;
    }
}

$conn->close();


// --- สร้างรายการออปชันสำหรับแต่ละหมวดหมู่ ---
$sticker_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['สติ๊กเกอร์']);
$vinyl_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['ผ้าไวนิล']);
$letter_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['ตัวอักษรโลหะ']);
$lightbox_options = array_merge($options_by_category['ทั่วไป'], $options_by_category['กล่องไฟ']);

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
        <div class="admin-link"><a href="admin.php" class="btn-admin">ไปหน้าจัดการ (Admin)</a></div>
        <div class="main-layout-grid">
            <div class="calculators-grid">

                <div class="calculator-section">
                    <h1>คำนวณราคาสติ๊กเกอร์</h1>
                    <p>(ราคาสติ๊กเกอร์พื้นฐานคือ <?php echo number_format($sticker_price_per_sqm, 2); ?> บาท/ตร.ม.)</p>
                    <form id="stickerForm">
                        <div><label for="st_width">ความกว้าง (ซม.):</label><input type="text" id="st_width"
                                name="st_width" placeholder="กรอกความกว้าง" value=""></div>
                        <div><label for="st_height">ความสูง (ซม.):</label><input type="text" id="st_height"
                                name="st_height" placeholder="กรอกความสูง" value=""></div>
                        <div><label for="st_quantity">จำนวน (แผ่น):</label><input type="text" id="st_quantity"
                                name="st_quantity" placeholder="กรอกจำนวน" value="1"></div>
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
                                <option value="in_city">ในเมือง (<?php echo number_format($travel_cost_in_city, 2); ?>
                                    บาท)
                                </option>
                                <option value="out_city">นอกเมือง (<?php echo number_format($travel_cost_per_km, 2); ?>
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
                                <option value="in_city">ในเมือง (<?php echo number_format($travel_cost_in_city, 2); ?>
                                    บาท)
                                </option>
                                <option value="out_city">นอกเมือง (<?php echo number_format($travel_cost_per_km, 2); ?>
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
                        <div><label for="letter_height">ความสูง (นิ้ว):</label><input type="text" id="letter_height"
                                name="letter_height" placeholder="กรอกความสูง" value=""></div>
                        <div><label for="letter_quantity">จำนวนตัวอักษร:</label><input type="text" id="letter_quantity"
                                name="letter_quantity" placeholder="กรอกจำนวน" value=""></div>
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
                                        name="lt_options[]" value="<?php echo $opt['option_id']; ?>">
                                    <label for="lt_opt_<?php echo $opt['option_id']; ?>">
                                        <?php echo htmlspecialchars($opt['option_name']) . " (" . number_format($opt['option_price'], 2) . " บาท)"; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="full-width-field"><label for="travel_type">ค่าเดินทาง:</label>
                            <select id="travel_type" name="travel_type">
                                <option value="none">ไม่รวมค่าเดินทาง</option>
                                <option value="in_city">ในเมือง (<?php echo number_format($travel_cost_in_city, 2); ?>
                                    บาท)
                                </option>
                                <option value="out_city">นอกเมือง (<?php echo number_format($travel_cost_per_km, 2); ?>
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
                        <div><label for="lb_width">ความกว้าง (ซม.):</label><input type="text" id="lb_width" name="lb_width" placeholder="กรอกความกว้าง" value=""></div>
                        <div><label for="lb_height">ความยาว/สูง (ซม.):</label><input type="text" id="lb_height" name="lb_height" placeholder="กรอกความยาว/สูง" value=""></div>
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
                                        for="lb_distance_km">ระยะทาง (กม.):</label><input type="text"
                                        id="lb_distance_km" name="lb_distance_km" value=""></div>
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
                                <td colspan="3">ไม่มีข้อมูลสินค้าในสต็อก</td>
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
    </div>

    <script src="js/index.js" defer></script>
</body>

</html>