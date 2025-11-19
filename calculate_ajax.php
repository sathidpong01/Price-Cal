<?php
/**
 * calculate_ajax.php
 * ทำหน้าที่รับข้อมูลจาก JavaScript (AJAX), คำนวณราคา, และส่งผลกลับเป็น JSON
 */
 // เรียกใช้ไฟล์เชื่อมต่อและไฟล์ดึงข้อมูล
 include 'includes/db_connect.php';
 include 'includes/data_fetcher.php';
 include 'includes/calculation_helpers.php'; // [NEW] เรียกใช้ Helper

 // --- ฟังก์ชันปัดเศษ ---
function customRound($number) {
    return floor(($number + 6) / 10) * 10;
}
 
 // --- ใช้ฟังก์ชันจาก data_fetcher.php เพื่อดึงข้อมูล ---
 $price_rules = getPriceRules($conn);
 $all_options_by_cat = getAllOptionsByCategory($conn);
 $all_materials = getAllMaterialsByType($conn);
 
 // --- กำหนดค่าตัวแปรจากข้อมูลที่ดึงมา ---
 $travel_cost_per_km = isset($price_rules['ติดตั้งนอกเมือง']) ? $price_rules['ติดตั้งนอกเมือง'] : 10;
 $travel_cost_in_city = isset($price_rules['ติดตั้งในเมือง']) ? $price_rules['ติดตั้งในเมือง'] : 300;
 
 // --- รวม Options ทั้งหมดเป็น array เดียวเพื่อให้ค้นหาง่ายขึ้นในไฟล์นี้ ---
 $options_list = [];
 foreach ($all_options_by_cat as $category => $opts) {
     $options_list = array_merge($options_list, $opts);
 }
 
 // --- แยกข้อมูล Materials สำหรับการคำนวณ ---
 $materials_for_letter = $all_materials['ตัวอักษรโลหะ'];
 $lightbox_materials = $all_materials['กล่องไฟ'];
 $sheet_list_for_sticker = $all_materials['วัสดุแผ่น'];
 $vinyl_materials = $all_materials['ผ้าไวนิล'];
 $sticker_materials = $all_materials['สติ๊กเกอร์'];

// --- เตรียมตัวแปรสำหรับตอบกลับ ---
$response = ['success' => false, 'error' => '', 'results' => null, 'calculator_type' => 'unknown'];

// --- ตรวจสอบและเริ่มคำนวณ ---
if (isset($_POST['calculator_type'])) {
    $calculator_type = $_POST['calculator_type'];
    $response['calculator_type'] = $calculator_type;

    try {
        // ========================== STICKER ==========================
        if ($calculator_type == 'sticker') {
            $st_width = $_POST['st_width'] ?? '0';
            $st_height = $_POST['st_height'] ?? '0';
            $st_quantity = isset($_POST['st_quantity']) && is_numeric($_POST['st_quantity']) && $_POST['st_quantity'] > 0 ? intval($_POST['st_quantity']) : 1;
            $st_selected_material_id = $_POST['st_material_type'] ?? '';
            $st_selected_sheet_id = $_POST['st_sheet_material'] ?? 'none';
            $st_selected_options = isset($_POST['st_options']) ? (is_array($_POST['st_options']) ? $_POST['st_options'] : []) : [];
            $st_travel_type = $_POST['st_travel_type'] ?? 'none';
            $st_distance_km = $_POST['st_distance_km'] ?? '0';

            if (!is_numeric($st_width) || !is_numeric($st_height) || $st_width <= 0 || $st_height <= 0 || empty($st_selected_material_id)) {
                $response['error'] = "กรุณากรอก กว้าง x สูง และเลือกชนิดสติ๊กเกอร์ให้ถูกต้อง";
            } else {
                $area_sqm = ($st_width / 100) * ($st_height / 100);
        
                // 1. หาวัสดุสติ๊กเกอร์
                $stickerMat = findMaterial($st_selected_material_id, $sticker_materials);
                
                if (!$stickerMat) {
                    $response['error'] = "ไม่พบราคาของชนิดสติ๊กเกอร์ที่เลือก";
                } else {
                    $base_sticker_price_per_sqm = $stickerMat['price_per_unit'];
                    $selected_sticker_material_name = $stickerMat['material_name'];
                    $sticker_only_price = $base_sticker_price_per_sqm * $area_sqm;

                    // 2. หาวัสดุแผ่นรองหลัง
                    $sheet_price = 0; 
                    $selected_sheet_name = '';
                    if ($st_selected_sheet_id != 'none') {
                        $sheetMat = findMaterial($st_selected_sheet_id, $sheet_list_for_sticker);
                        if ($sheetMat) {
                            $sheet_price = $sheetMat['price_per_unit'] * $area_sqm;
                            $selected_sheet_name = $sheetMat['material_name'];
                        }
                    }

                    // 3. คำนวณ Options (ใช้ Helper)
                    $optionsResult = calculateOptionsPrice($st_selected_options, $options_list);
                    $st_options_price_total = $optionsResult['total_price'];
                    $st_selected_options_details = $optionsResult['details'];

                    // 4. คำนวณค่าเดินทาง (ใช้ Helper)
                    $travelResult = calculateTravelCost($st_travel_type, $st_distance_km, $travel_cost_in_city, $travel_cost_per_km);
                    $st_travel_cost = $travelResult['cost'];
                    $st_travel_description = $travelResult['description'];

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
                        'sticker_material_name' => $selected_sticker_material_name,
                        'sheet_name' => $selected_sheet_name,
                        'sheet_price' => $sheet_price,
                        'options' => $st_selected_options_details,
                        'travel_desc' => $st_travel_description,
                        'travel_price' => $st_travel_cost,
                        'total_price' => $st_total_price
                    ];
                }
            }
        }
        // ========================== LETTER ==========================
        elseif ($calculator_type == 'letter') {
            $lt_height = $_POST['letter_height'] ?? '0'; 
            $lt_quantity = $_POST['letter_quantity'] ?? '0';
            $lt_selected_material_id = $_POST['material'] ?? '';
            $lt_selected_options = isset($_POST['lt_options']) ? (is_array($_POST['lt_options']) ? $_POST['lt_options'] : []) : [];
            $lt_travel_type = $_POST['travel_type'] ?? 'none'; 
            $lt_distance_km = $_POST['distance_km'] ?? '0';

            if (!is_numeric($lt_height) || !is_numeric($lt_quantity) || empty($lt_selected_material_id) || $lt_height <= 0 || $lt_quantity <= 0) {
                $response['error'] = "กรุณากรอก ความสูง (>0), จำนวน (>0) และเลือกวัสดุ";
            } else {
                // 1. หาวัสดุ
                $letterMat = findMaterial($lt_selected_material_id, $materials_for_letter);
                
                if (!$letterMat) {
                     $response['error'] = "ไม่พบราคาวัสดุ"; 
                } else {
                    $material_price_per_unit = $letterMat['price_per_unit'];
                    $selected_material_name = $letterMat['material_name'];
                    $selected_material_unit = $letterMat['unit'];
                    $base_price = $lt_height * $lt_quantity * $material_price_per_unit;

                    // 2. คำนวณ Options (ใช้ Helper)
                    $optionsResult = calculateOptionsPrice($lt_selected_options, $options_list);
                    
                    // 3. คำนวณค่าเดินทาง (ใช้ Helper)
                    $travelResult = calculateTravelCost($lt_travel_type, $lt_distance_km, $travel_cost_in_city, $travel_cost_per_km);

                    $lt_total_price = customRound($base_price + $optionsResult['total_price'] + $travelResult['cost']);
                    
                    $response['success'] = true;
                    $response['results'] = [ 
                        'height' => $lt_height, 
                        'quantity' => $lt_quantity, 
                        'material_name' => $selected_material_name, 
                        'material_price_pu' => $material_price_per_unit, 
                        'material_unit' => $selected_material_unit, 
                        'base_price' => $base_price, 
                        'options' => $optionsResult['details'], 
                        'travel_desc' => $travelResult['description'], 
                        'travel_price' => $travelResult['cost'], 
                        'total_price' => $lt_total_price 
                    ];
                }
            }
        }
        // ========================== LIGHTBOX ==========================
        elseif ($calculator_type == 'lightbox') {
            $lb_width = $_POST['lb_width'] ?? '0'; 
            $lb_height = $_POST['lb_height'] ?? '0';
            $lb_selected_id = $_POST['lightbox_type'] ?? '';
            $lb_selected_options = isset($_POST['lb_options']) ? (is_array($_POST['lb_options']) ? $_POST['lb_options'] : []) : [];
            $lb_travel_type = $_POST['lb_travel_type'] ?? 'none'; 
            $lb_distance_km = $_POST['lb_distance_km'] ?? '0';

            // 1. หาวัสดุ
            $lightboxMat = findMaterial($lb_selected_id, $lightbox_materials);
            $lightbox_price_per_sqm = $lightboxMat ? $lightboxMat['price_per_unit'] : 0;
            $selected_lightbox_name = $lightboxMat ? $lightboxMat['material_name'] : '';

            if (!is_numeric($lb_width) || !is_numeric($lb_height) || empty($lb_selected_id) || $lightbox_price_per_sqm <= 0 || $lb_width <= 0 || $lb_height <= 0) {
                $response['error'] = "กรุณากรอก กว้าง (>0), ยาว (>0) และเลือกประเภทกล่องไฟ";
            } else {
                $area_sqm = 0; 
                $width_m = $lb_width / 100; 
                $height_m = $lb_height / 100; 
                $shape = '';
                
                if (strpos($selected_lightbox_name, 'สี่เหลี่ยม') !== false) { 
                    $area_sqm = $width_m * $height_m; 
                    $shape = 'สี่เหลี่ยม'; 
                } elseif (strpos($selected_lightbox_name, 'วงกลม') !== false) { 
                    $diameter_m = $width_m; 
                    $radius_m = $diameter_m / 2; 
                    $area_sqm = M_PI * pow($radius_m, 2); 
                    $shape = 'วงกลม'; 
                    if ($lb_width != $lb_height && $response['error'] == '') { 
                        $response['error'] = "แนะนำ: วงกลม ควรใส่ กว้าง = ยาว."; 
                    } 
                } else { 
                    $response['error'] = "ไม่สามารถระบุรูปทรงได้"; 
                }

                if (empty($response['error']) || (strpos($response['error'],"แนะนำ:") === 0 && $area_sqm > 0) ) {
                    if(strpos($response['error'],"แนะนำ:") === 0) $response['warning'] = $response['error'];
                    
                    $base_price = $area_sqm * $lightbox_price_per_sqm;
                    
                    // 2. คำนวณ Options (ใช้ Helper)
                    $optionsResult = calculateOptionsPrice($lb_selected_options, $options_list);
                    
                    // 3. คำนวณค่าเดินทาง (ใช้ Helper)
                    $travelResult = calculateTravelCost($lb_travel_type, $lb_distance_km, $travel_cost_in_city, $travel_cost_per_km);

                    $lb_total_price = customRound($base_price + $optionsResult['total_price'] + $travelResult['cost']);
                    
                    $response['success'] = true;
                    $response['results'] = [ 
                        'area' => $area_sqm, 
                        'shape' => $shape, 
                        'width' => $lb_width, 
                        'height' => $lb_height, 
                        'type_name' => $selected_lightbox_name, 
                        'base_price' => $base_price, 
                        'options' => $optionsResult['details'], 
                        'travel_desc' => $travelResult['description'], 
                        'travel_price' => $travelResult['cost'], 
                        'total_price' => $lb_total_price 
                    ];
                     if(isset($response['warning'])) $response['error'] = $response['warning'];
                     else $response['error'] = '';
                } elseif ($area_sqm <=0 && empty($response['error'])) {
                    $response['error'] = "ไม่สามารถคำนวณพื้นที่กล่องไฟได้";
                }
            }
        }
        // ========================== VINYL ==========================
        elseif ($calculator_type == 'vinyl') {
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
                
                // 1. หาวัสดุ
                $vinylMat = findMaterial($vn_selected_material_id, $vinyl_materials);
                
                if (!$vinylMat) {
                    $response['error'] = "ไม่พบชนิดผ้าไวนิลที่เลือก";
                } else {
                    $base_vinyl_price_per_sqm = $vinylMat['price_per_unit'];
                    $selected_vinyl_material_name = $vinylMat['material_name'];
                    $vinyl_material_cost = $base_vinyl_price_per_sqm * $area_sqm;

                    // 2. คำนวณ Options (ใช้ Helper)
                    $optionsResult = calculateOptionsPrice($vn_selected_options, $options_list);

                    // 3. คำนวณค่าเดินทาง (ใช้ Helper)
                    $travelResult = calculateTravelCost($vn_travel_type, $vn_distance_km, $travel_cost_in_city, $travel_cost_per_km);

                    $price_per_piece = customRound($vinyl_material_cost + $optionsResult['total_price'] + $travelResult['cost']);
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
                        'options' => $optionsResult['details'],
                        'travel_desc' => $travelResult['description'],
                        'travel_price' => $travelResult['cost'],
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

// --- ปิดการเชื่อมต่อ และส่งผลกลับ ---
$conn->close();
echo json_encode($response);
exit;
?>