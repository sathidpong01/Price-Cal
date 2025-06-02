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
$sticker_price_per_sqm = isset($price_rules['ราคาสติ๊กเกอร์ต่อตรม.']) ? $price_rules['ราคาสติ๊กเกอร์ต่อตรม.'] : 450;
$travel_cost_per_km = isset($price_rules['ติดตั้งนอกเมือง']) ? $price_rules['ติดตั้งนอกเมือง'] : 10;
$travel_cost_in_city = isset($price_rules['ติดตั้งในเมือง']) ? $price_rules['ติดตั้งในเมือง'] : 300;
// เพิ่ม rule สำหรับไวนิล ถ้ามีราคา default
// $default_vinyl_price_sqm = isset($price_rules['Default Vinyl Price SQM']) ? $price_rules['Default Vinyl Price SQM'] : 400;


$options_list = [];
$sql_options = "SELECT option_id, option_name, option_price FROM options";
$result_options = $conn->query($sql_options);
if ($result_options) { while ($row = $result_options->fetch_assoc()) { $options_list[] = $row; } }

$materials_for_letter = [];
$lightbox_materials = [];
$sheet_list_for_sticker = [];
$vinyl_materials = []; // เพิ่ม

$sql_all_materials = "SELECT material_id, product_type, material_name, price_per_unit, unit FROM materials";
$result_all_materials_query = $conn->query($sql_all_materials); // เปลี่ยนชื่อตัวแปร result
if ($result_all_materials_query) { // ใช้ตัวแปรใหม่
    while ($row_mat = $result_all_materials_query->fetch_assoc()) { // ใช้ตัวแปรใหม่
        if ($row_mat['product_type'] == 'ตัวอักษรโลหะ') { $materials_for_letter[] = $row_mat; }
        elseif ($row_mat['product_type'] == 'กล่องไฟ') { $lightbox_materials[] = $row_mat; }
        elseif ($row_mat['product_type'] == 'วัสดุแผ่น') { $sheet_list_for_sticker[] = $row_mat; }
        elseif ($row_mat['product_type'] == 'ผ้าไวนิล') { $vinyl_materials[] = $row_mat; } // เพิ่ม
    }
}

// --- 4. เตรียมตัวแปรสำหรับตอบกลับ ---
$response = ['success' => false, 'error' => '', 'results' => null, 'calculator_type' => 'unknown'];

// --- 5. ตรวจสอบและเริ่มคำนวณ ---
if (isset($_POST['calculator_type'])) {
    $calculator_type = $_POST['calculator_type'];
    $response['calculator_type'] = $calculator_type;

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
                    'width' => $st_width,
                    'height' => $st_height,
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
            $lt_selected_material_id = $_POST['material'] ?? ''; // Corresponds to name="material" in index.php
            $lt_selected_options = isset($_POST['lt_options']) ? (is_array($_POST['lt_options']) ? $_POST['lt_options'] : []) : []; // Changed 'options' to 'lt_options'
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

                if (empty($response['error']) || (strpos($response['error'],"แนะนำ:") === 0 && $area_sqm > 0) ) {
                    if(strpos($response['error'],"แนะนำ:") === 0) $response['warning'] = $response['error'];
                    $base_price = $area_sqm * $lightbox_price_per_sqm;
                    $lb_options_price_total = 0; $lb_selected_options_details = [];
                    foreach ($options_list as $opt) { if (in_array($opt['option_id'], $lb_selected_options)) { $lb_options_price_total += $opt['option_price']; $lb_selected_options_details[] = ['name' => $opt['option_name'], 'price' => $opt['option_price']]; } }
                    $lb_travel_cost = 0; $lb_travel_description = 'ไม่รวมค่าเดินทาง';
                    if ($lb_travel_type == 'in_city') { $lb_travel_cost = $travel_cost_in_city; $lb_travel_description = 'ในเมือง'; }
                    elseif ($lb_travel_type == 'out_city') { if (is_numeric($lb_distance_km) && $lb_distance_km > 0) { $lb_travel_cost = $lb_distance_km * $travel_cost_per_km; $lb_travel_description = "นอกเมือง ({$lb_distance_km} กม.)"; } }

                    $lb_total_price = customRound($base_price + $lb_options_price_total + $lb_travel_cost);
                    $response['success'] = true;
                    $response['results'] = [ 'area' => $area_sqm, 'shape' => $shape, 'width' => $lb_width, 'height' => $lb_height, 'type_name' => $selected_lightbox_name, 'base_price' => $base_price, 'options' => $lb_selected_options_details, 'travel_desc' => $lb_travel_description, 'travel_price' => $lb_travel_cost, 'total_price' => $lb_total_price ];
                     if(isset($response['warning'])) $response['error'] = $response['warning'];
                     else $response['error'] = '';
                } elseif ($area_sqm <=0 && empty($response['error'])) {
                    $response['error'] = "ไม่สามารถคำนวณพื้นที่กล่องไฟได้";
                }
            }
        }
        // ========================== VINYL ==========================
        elseif ($calculator_type == 'vinyl') {
            $response['calculator_type'] = 'vinyl';

            $vn_width = $_POST['vn_width'] ?? '0';
            $vn_height = $_POST['vn_height'] ?? '0';
            $vn_quantity = isset($_POST['vn_quantity']) && is_numeric($_POST['vn_quantity']) && $_POST['vn_quantity'] > 0 ? intval($_POST['vn_quantity']) : 1;
            $vn_selected_material_id = $_POST['vn_material_type'] ?? '';
            $vn_selected_options = isset($_POST['vn_options']) ? (is_array($_POST['vn_options']) ? $_POST['vn_options'] : []) : [];
            $vn_travel_type = $_POST['vn_travel_type'] ?? 'none';
            $vn_distance_km = $_POST['vn_distance_km'] ?? '0';

            if (!is_numeric($vn_width) || !is_numeric($vn_height) || $vn_width <= 0 || $vn_height <= 0 ) {
                $response['error'] = "กรุณากรอก กว้าง x สูง ให้ถูกต้อง";
            } else {
                $area_sqm = ($vn_width / 100) * ($vn_height / 100);
                $base_vinyl_price_per_sqm = 0;
                $selected_vinyl_material_name = '';

                if (!empty($vn_selected_material_id)) {
                    foreach ($vinyl_materials as $vn_mat) {
                        if ($vn_mat['material_id'] == $vn_selected_material_id) {
                            $base_vinyl_price_per_sqm = $vn_mat['price_per_unit'];
                            $selected_vinyl_material_name = $vn_mat['material_name'];
                            break;
                        }
                    }
                    if(empty($selected_vinyl_material_name)){ // ถ้า ID ที่ส่งมา ไม่มีใน list
                         $response['error'] = "ไม่พบชนิดผ้าไวนิลที่เลือก";
                    }
                } else {
                    // ถ้าไม่ได้บังคับเลือกชนิดผ้า และต้องการให้มีราคา default
                    // $base_vinyl_price_per_sqm = $default_vinyl_price_sqm; // ใช้ตัวแปรที่ตั้งค่าไว้ด้านบน
                    // $selected_vinyl_material_name = "ไวนิลมาตรฐาน (Default)";
                    $response['error'] = "กรุณาเลือกชนิดผ้าไวนิล"; // หรือจะบังคับเลือก
                }

                if (empty($response['error'])) {
                    $vinyl_material_cost = $base_vinyl_price_per_sqm * $area_sqm;

                    $vn_options_price_total = 0;
                    $vn_selected_options_details = [];
                    foreach ($options_list as $opt) {
                        if (in_array($opt['option_id'], $vn_selected_options)) {
                            $vn_options_price_total += $opt['option_price'];
                            $vn_selected_options_details[] = ['name' => $opt['option_name'], 'price' => $opt['option_price']];
                        }
                    }

                    $vn_travel_cost = 0;
                    $vn_travel_description = 'ไม่รวมค่าเดินทาง';
                    if ($vn_travel_type == 'in_city') {
                        $vn_travel_cost = $travel_cost_in_city;
                        $vn_travel_description = 'ในเมือง';
                    } elseif ($vn_travel_type == 'out_city') {
                        if (is_numeric($vn_distance_km) && $vn_distance_km > 0) {
                            $vn_travel_cost = $vn_distance_km * $travel_cost_per_km;
                            $vn_travel_description = "นอกเมือง ({$vn_distance_km} กม.)";
                        }
                    }

                    $price_per_piece = customRound($vinyl_material_cost + $vn_options_price_total + $vn_travel_cost);
                    $vn_total_price = $price_per_piece * $vn_quantity;

                    $response['success'] = true;
                    $response['results'] = [
                        'width' => $vn_width,
                        'height' => $vn_height,
                        'area' => $area_sqm,
                        'quantity' => $vn_quantity,
                        'vinyl_material_name' => $selected_vinyl_material_name,
                        'vinyl_material_price_sqm' => $base_vinyl_price_per_sqm,
                        'vinyl_base_price' => $vinyl_material_cost,
                        'options' => $vn_selected_options_details,
                        'travel_desc' => $vn_travel_description,
                        'travel_price' => $vn_travel_cost,
                        'price_per_piece' => $price_per_piece,
                        'total_price' => $vn_total_price
                    ];
                }
            }
        }
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