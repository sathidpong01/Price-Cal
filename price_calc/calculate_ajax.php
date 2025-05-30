<?php
/**
 * calculate_ajax.php
 * ทำหน้าที่รับข้อมูลจาก JavaScript (AJAX), คำนวณราคา, และส่งผลกลับเป็น JSON
 */

// --- 1. ตั้งค่าพื้นฐาน ---
header('Content-Type: application/json; charset=utf-8');
include 'includes/db_connect.php';

// --- 2. ฟังก์ชันปัดเศษ ---
function customRound($number) {
    return floor(($number + 6) / 10) * 10;
}

// --- 3. ดึงข้อมูลจำเป็นจากฐานข้อมูล ---
$price_rules = [];
$sql_rules = "SELECT rule_name, rule_value FROM price_rules";
$result_rules = $conn->query($sql_rules);
if ($result_rules) { while ($row = $result_rules->fetch_assoc()) { $price_rules[$row['rule_name']] = $row['rule_value']; } }
$sticker_price_per_sqm = isset($price_rules['Sticker Price Per SQM']) ? $price_rules['Sticker Price Per SQM'] : 500;
$travel_cost_per_km = isset($price_rules['Travel Cost Per KM']) ? $price_rules['Travel Cost Per KM'] : 10;
$travel_cost_in_city = isset($price_rules['Travel Cost In City']) ? $price_rules['Travel Cost In City'] : 500;

$options_list = [];
$sql_options = "SELECT option_id, option_name, option_price FROM options";
$result_options = $conn->query($sql_options);
if ($result_options) { while ($row = $result_options->fetch_assoc()) { $options_list[] = $row; } }

$materials_for_letter = [];
$lightbox_materials = [];
$sheet_list_for_sticker = [];
$sql_all_materials = "SELECT material_id, product_type, material_name, price_per_unit, unit FROM materials";
$result_all_materials = $conn->query($sql_all_materials);
if ($result_all_materials) {
    while ($row_mat = $result_all_materials->fetch_assoc()) {
        if ($row_mat['product_type'] == 'ตัวอักษรโลหะ') { $materials_for_letter[] = $row_mat; }
        elseif ($row_mat['product_type'] == 'กล่องไฟ') { $lightbox_materials[] = $row_mat; }
        elseif ($row_mat['product_type'] == 'วัสดุแผ่น') { $sheet_list_for_sticker[] = $row_mat; }
    }
}

// --- 4. เตรียมตัวแปรสำหรับตอบกลับ ---
$response = ['success' => false, 'error' => '', 'results' => null, 'calculator_type' => 'unknown'];

// --- 5. ตรวจสอบและเริ่มคำนวณ ---
if (isset($_POST['calculator_type'])) {
    $calculator_type = $_POST['calculator_type'];
    $response['calculator_type'] = $calculator_type; // ส่งกลับไปให้ JS รู้ว่าคำนวณอะไร

    try {
        // ========================== STICKER ==========================
        if ($calculator_type == 'sticker') {
            $st_width = $_POST['st_width'] ?? '0'; $st_height = $_POST['st_height'] ?? '0';
            $st_quantity = isset($_POST['st_quantity']) && is_numeric($_POST['st_quantity']) && $_POST['st_quantity'] > 0 ? intval($_POST['st_quantity']) : 1;
            $st_selected_sheet_id = $_POST['st_sheet_material'] ?? 'none';
            $st_selected_options = isset($_POST['st_options']) ? (is_array($_POST['st_options']) ? $_POST['st_options'] : []) : [];
            $st_travel_type = $_POST['st_travel_type'] ?? 'none'; $st_distance_km = $_POST['st_distance_km'] ?? '0';

            if (!is_numeric($st_width) || !is_numeric($st_height) || $st_width <= 0 || $st_height <= 0) {
                $response['error'] = "กรุณากรอก กว้าง x สูง ให้ถูกต้อง";
            } else {
                $area_sqm = ($st_width / 100) * ($st_height / 100);
                $sticker_only_price = $sticker_price_per_sqm * $area_sqm;
                $sheet_price = 0; $selected_sheet_name = '';
                if ($st_selected_sheet_id != 'none') {
                    $sheet_ppu = 0;
                    foreach ($sheet_list_for_sticker as $sh) { if ($sh['material_id'] == $st_selected_sheet_id) { $sheet_ppu = $sh['price_per_unit']; $selected_sheet_name = $sh['material_name']; break; } }
                    if ($sheet_ppu > 0) { $sheet_price = $sheet_ppu * $area_sqm; }
                }
                $st_options_price_total = 0; $st_selected_options_details = [];
                foreach ($options_list as $opt) { if (in_array($opt['option_id'], $st_selected_options)) { $st_options_price_total += $opt['option_price']; $st_selected_options_details[] = ['name' => $opt['option_name'], 'price' => $opt['option_price']]; } }
                $st_travel_cost = 0; $st_travel_description = 'ไม่รวมค่าเดินทาง';
                if ($st_travel_type == 'in_city') { $st_travel_cost = $travel_cost_in_city; $st_travel_description = 'ในเมือง'; }
                elseif ($st_travel_type == 'out_city') { if (is_numeric($st_distance_km) && $st_distance_km > 0) { $st_travel_cost = $st_distance_km * $travel_cost_per_km; $st_travel_description = "นอกเมือง ({$st_distance_km} กม.)"; } }

                $price_per_sheet = customRound($sticker_only_price + $sheet_price + $st_options_price_total + $st_travel_cost);
                $st_total_price = $price_per_sheet * $st_quantity;
                $response['success'] = true;
                $response['results'] = [
                    'width' => $st_width, // <-- เพิ่มบรรทัดนี้
                    'height' => $st_height, // <-- เพิ่มบรรทัดนี้
                    'area' => $area_sqm,
                    'quantity' => $st_quantity,
                    'price_per_sheet' => $price_per_sheet,
                    'sticker_price' => $sticker_only_price,
                    'sheet_name' => $selected_sheet_name,
                    'sheet_price' => $sheet_price,
                    'options' => $st_selected_options_details,
                    'travel_desc' => $st_travel_description,
                    'travel_price' => $st_travel_cost,
                    'total_price' => $st_total_price
                ];
            }
        }
        // ========================== LETTER ==========================
        elseif ($calculator_type == 'letter') {
            $lt_height = $_POST['letter_height'] ?? '0'; $lt_quantity = $_POST['letter_quantity'] ?? '0';
            $lt_selected_material_id = $_POST['material'] ?? '';
            $lt_selected_options = isset($_POST['options']) ? (is_array($_POST['options']) ? $_POST['options'] : []) : [];
            $lt_travel_type = $_POST['travel_type'] ?? 'none'; $lt_distance_km = $_POST['distance_km'] ?? '0';

            if (!is_numeric($lt_height) || !is_numeric($lt_quantity) || empty($lt_selected_material_id) || $lt_height <= 0 || $lt_quantity <= 0) {
                $response['error'] = "กรุณากรอก ความสูง (>0), จำนวน (>0) และเลือกวัสดุ";
            } else {
                $material_price_per_unit = 0; $selected_material_name = ''; $selected_material_unit = '';
                foreach ($materials_for_letter as $mat) { if ($mat['material_id'] == $lt_selected_material_id) { $material_price_per_unit = $mat['price_per_unit']; $selected_material_name = $mat['material_name']; $selected_material_unit = $mat['unit']; break; } }
                $base_price = 0;
                if ($material_price_per_unit > 0) { $base_price = $lt_height * $lt_quantity * $material_price_per_unit; } else { $response['error'] = "ไม่พบราคาวัสดุ"; }

                if (empty($response['error'])) {
                    $lt_options_price_total = 0; $lt_selected_options_details = [];
                    foreach ($options_list as $opt) { if (in_array($opt['option_id'], $lt_selected_options)) { $lt_options_price_total += $opt['option_price']; $lt_selected_options_details[] = ['name' => $opt['option_name'], 'price' => $opt['option_price']]; } }
                    $lt_travel_cost = 0; $lt_travel_description = 'ไม่รวมค่าเดินทาง';
                    if ($lt_travel_type == 'in_city') { $lt_travel_cost = $travel_cost_in_city; $lt_travel_description = 'ในเมือง'; }
                    elseif ($lt_travel_type == 'out_city') { if (is_numeric($lt_distance_km) && $lt_distance_km > 0) { $lt_travel_cost = $lt_distance_km * $travel_cost_per_km; $lt_travel_description = "นอกเมือง ({$lt_distance_km} กม.)"; } }

                    $lt_total_price = customRound($base_price + $lt_options_price_total + $lt_travel_cost);
                    $response['success'] = true;
                    $response['results'] = [ 'height' => $lt_height, 'quantity' => $lt_quantity, 'material_name' => $selected_material_name, 'material_price_pu' => $material_price_per_unit, 'material_unit' => $selected_material_unit, 'base_price' => $base_price, 'options' => $lt_selected_options_details, 'travel_desc' => $lt_travel_description, 'travel_price' => $lt_travel_cost, 'total_price' => $lt_total_price ];
                }
            }
        }
        // ========================== LIGHTBOX ==========================
        elseif ($calculator_type == 'lightbox') {
            $lb_width = $_POST['lb_width'] ?? '0'; $lb_height = $_POST['lb_height'] ?? '0';
            $lb_selected_id = $_POST['lightbox_type'] ?? '';
            $lb_selected_options = isset($_POST['lb_options']) ? (is_array($_POST['lb_options']) ? $_POST['lb_options'] : []) : [];
            $lb_travel_type = $_POST['lb_travel_type'] ?? 'none'; $lb_distance_km = $_POST['lb_distance_km'] ?? '0';

            $lightbox_price_per_sqm = 0; $selected_lightbox_name = '';
            foreach ($lightbox_materials as $lb) { if ($lb['material_id'] == $lb_selected_id) { $lightbox_price_per_sqm = $lb['price_per_unit']; $selected_lightbox_name = $lb['material_name']; break; } }

            if (!is_numeric($lb_width) || !is_numeric($lb_height) || empty($lb_selected_id) || $lightbox_price_per_sqm <= 0 || $lb_width <= 0 || $lb_height <= 0) {
                $response['error'] = "กรุณากรอก กว้าง (>0), ยาว (>0) และเลือกประเภทกล่องไฟ";
            } else {
                $area_sqm = 0; $width_m = $lb_width / 100; $height_m = $lb_height / 100; $shape = '';
                if (strpos($selected_lightbox_name, 'สี่เหลี่ยม') !== false) { $area_sqm = $width_m * $height_m; $shape = 'สี่เหลี่ยม'; }
                elseif (strpos($selected_lightbox_name, 'วงกลม') !== false) { $diameter_m = $width_m; $radius_m = $diameter_m / 2; $area_sqm = M_PI * pow($radius_m, 2); $shape = 'วงกลม'; if ($lb_width != $lb_height && $response['error'] == '') { $response['error'] = "แนะนำ: วงกลม ควรใส่ กว้าง = ยาว."; } }
                else { $response['error'] = "ไม่สามารถระบุรูปทรงได้"; }

                if (empty($response['error']) || (strpos($response['error'],"แนะนำ:") === 0 && $area_sqm > 0) ) { //ถ้าเป็นแค่คำแนะนำ ให้คำนวณต่อ
                    if(strpos($response['error'],"แนะนำ:") === 0) $response['warning'] = $response['error']; //เก็บ warning ไว้

                    $base_price = $area_sqm * $lightbox_price_per_sqm;
                    $lb_options_price_total = 0; $lb_selected_options_details = [];
                    foreach ($options_list as $opt) { if (in_array($opt['option_id'], $lb_selected_options)) { $lb_options_price_total += $opt['option_price']; $lb_selected_options_details[] = ['name' => $opt['option_name'], 'price' => $opt['option_price']]; } }
                    $lb_travel_cost = 0; $lb_travel_description = 'ไม่รวมค่าเดินทาง';
                    if ($lb_travel_type == 'in_city') { $lb_travel_cost = $travel_cost_in_city; $lb_travel_description = 'ในเมือง'; }
                    elseif ($lb_travel_type == 'out_city') { if (is_numeric($lb_distance_km) && $lb_distance_km > 0) { $lb_travel_cost = $lb_distance_km * $travel_cost_per_km; $lb_travel_description = "นอกเมือง ({$lb_distance_km} กม.)"; } }

                    $lb_total_price = customRound($base_price + $lb_options_price_total + $lb_travel_cost);
                    $response['success'] = true;
                    $response['results'] = [ 'area' => $area_sqm, 'shape' => $shape, 'width' => $lb_width, 'height' => $lb_height, 'type_name' => $selected_lightbox_name, 'base_price' => $base_price, 'options' => $lb_selected_options_details, 'travel_desc' => $lb_travel_description, 'travel_price' => $lb_travel_cost, 'total_price' => $lb_total_price ];
                     if(isset($response['warning'])) $response['error'] = $response['warning']; //คืนค่า error ถ้ามี warning
                     else $response['error'] = ''; //เคลียร์ error ถ้าไม่มี warning

                } elseif ($area_sqm <=0 && empty($response['error'])) {
                    $response['error'] = "ไม่สามารถคำนวณพื้นที่กล่องไฟได้";
                }
            }
        }
         // --- จบการคำนวณ ---
        else {
            $response['error'] = 'ประเภทการคำนวณไม่รู้จัก';
        }
    } catch (Exception $e) {
        $response['error'] = 'เกิดข้อผิดพลาดทั่วไป: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'ไม่ได้ระบุประเภทการคำนวณ';
}

// --- 6. ปิดการเชื่อมต่อ และส่งผลกลับ ---
$conn->close();
echo json_encode($response);
exit;
?>