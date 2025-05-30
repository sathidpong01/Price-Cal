<?php
/**
 * calculate_ajax.php (OOP version)
 * รับข้อมูลจาก JavaScript (AJAX), คำนวณราคา, และส่งผลกลับเป็น JSON
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . 'autoloader.php';

use App\Managers\PriceRuleManager;
use App\Managers\OptionManager;
use App\Managers\MaterialManager;
use App\Utils\PriceRounder;
use App\Calculators\StickerCalculator;
use App\Calculators\VinylCalculator;
use App\Calculators\LetterCalculator;
use App\Calculators\LightboxCalculator;

$response = ['success' => false, 'error' => '', 'results' => null, 'calculator_type' => 'unknown'];

try {
    $priceRuleManager = new PriceRuleManager();
    $optionManager = new OptionManager();
    $materialManager = new MaterialManager();
    $priceRounder = new PriceRounder();

    if (isset($_POST['calculator_type'])) {
        $calculator_type = $_POST['calculator_type'];
        $response['calculator_type'] = $calculator_type;

        if ($calculator_type === 'sticker') {
            $st_width = floatval($_POST['st_width'] ?? 0);
            $st_height = floatval($_POST['st_height'] ?? 0);
            $st_quantity = isset($_POST['st_quantity']) && is_numeric($_POST['st_quantity']) && $_POST['st_quantity'] > 0 ? intval($_POST['st_quantity']) : 1;
            $st_selected_sheet_id = $_POST['st_sheet_material'] ?? 'none';
            $st_selected_options = isset($_POST['st_options']) ? (is_array($_POST['st_options']) ? $_POST['st_options'] : []) : [];
            $st_travel_type = $_POST['st_travel_type'] ?? 'none';
            $st_distance_km = $_POST['st_distance_km'] ?? '';

            if ($st_width <= 0 || $st_height <= 0) {
                $response['error'] = "กรุณากรอก กว้าง x สูง ให้ถูกต้อง";
            } else {
                $calculator = new StickerCalculator($priceRuleManager, $optionManager, $materialManager, $priceRounder);
                $calculator->setDimensions($st_width, $st_height, $st_quantity);
                // Sheet material
                $sheetMaterial = null;
                if ($st_selected_sheet_id !== 'none') {
                    $sheetMaterial = $materialManager->getMaterialById((int)$st_selected_sheet_id);
                }
                $calculator->setSheetMaterial($sheetMaterial);
                $calculator->setSelectedOptions(array_map('intval', $st_selected_options));
                $calculator->setTravelInfo($st_travel_type, is_numeric($st_distance_km) ? floatval($st_distance_km) : null);
                $calculator->calculate();

                // Prepare result for frontend compatibility
                $areaSqm = ($st_width * $st_height) / 10000;
                $sheet_name = $sheetMaterial ? $sheetMaterial->material_name : '';
                $sheet_price = $sheetMaterial ? $sheetMaterial->calculatePrice($areaSqm) : 0;
                $options = [];
                foreach ($st_selected_options as $oid) {
                    $opt = $optionManager->getOptionById((int)$oid);
                    if ($opt) {
                        $options[] = ['name' => $opt->option_name, 'price' => $opt->option_price];
                    }
                }
                $travel_price = 0;
                $travel_desc = 'ไม่รวมค่าเดินทาง';
                foreach ($calculator->getPriceBreakdown() as $comp) {
                    if ($comp['name'] === 'ค่าเดินทาง') {
                        $travel_price = $comp['price'];
                        $travel_desc = $comp['description'];
                    }
                }
                $response['success'] = true;
                $response['results'] = [
                    'width' => $st_width,
                    'height' => $st_height,
                    'area' => $areaSqm,
                    'quantity' => $st_quantity,
                    'price_per_sheet' => $calculator->getTotalPrice(),
                    'sticker_price' => null, // ไม่แยก sticker only ใน OOP version
                    'sheet_name' => $sheet_name,
                    'sheet_price' => $sheet_price,
                    'options' => $options,
                    'travel_desc' => $travel_desc,
                    'travel_price' => $travel_price,
                    'total_price' => $calculator->getTotalPrice()
                ];
            }
        }
        elseif ($calculator_type === 'vinyl') {
            $vn_width = floatval($_POST['vn_width'] ?? 0);
            $vn_height = floatval($_POST['vn_height'] ?? 0);
            $vn_quantity = isset($_POST['vn_quantity']) && is_numeric($_POST['vn_quantity']) && $_POST['vn_quantity'] > 0 ? intval($_POST['vn_quantity']) : 1;
            $vn_selected_material_id = $_POST['vn_material_type'] ?? '';
            $vn_selected_options = isset($_POST['vn_options']) ? (is_array($_POST['vn_options']) ? $_POST['vn_options'] : []) : [];
            $vn_travel_type = $_POST['vn_travel_type'] ?? 'none';
            $vn_distance_km = $_POST['vn_distance_km'] ?? '';

            if ($vn_width <= 0 || $vn_height <= 0) {
                $response['error'] = "กรุณากรอก กว้าง x สูง ให้ถูกต้อง";
            } elseif (empty($vn_selected_material_id)) {
                $response['error'] = "กรุณาเลือกชนิดผ้าไวนิล";
            } else {
                $calculator = new VinylCalculator($priceRuleManager, $optionManager, $materialManager, $priceRounder);
                $calculator->setDimensions($vn_width, $vn_height, $vn_quantity);
                $vinylMaterial = $materialManager->getMaterialById((int)$vn_selected_material_id);
                $calculator->setVinylMaterial($vinylMaterial);
                $calculator->setSelectedOptions(array_map('intval', $vn_selected_options));
                $calculator->setTravelInfo($vn_travel_type, is_numeric($vn_distance_km) ? floatval($vn_distance_km) : null);
                $calculator->calculate();

                $areaSqm = ($vn_width * $vn_height) / 10000;
                $options = [];
                foreach ($vn_selected_options as $oid) {
                    $opt = $optionManager->getOptionById((int)$oid);
                    if ($opt) {
                        $options[] = ['name' => $opt->option_name, 'price' => $opt->option_price];
                    }
                }
                $travel_price = 0;
                $travel_desc = 'ไม่รวมค่าเดินทาง';
                foreach ($calculator->getPriceBreakdown() as $comp) {
                    if ($comp['name'] === 'ค่าเดินทาง') {
                        $travel_price = $comp['price'];
                        $travel_desc = $comp['description'];
                    }
                }
                $response['success'] = true;
                $response['results'] = [
                    'width' => $vn_width,
                    'height' => $vn_height,
                    'area' => $areaSqm,
                    'quantity' => $vn_quantity,
                    'vinyl_material_name' => $vinylMaterial ? $vinylMaterial->material_name : '',
                    'vinyl_material_price_sqm' => $vinylMaterial ? $vinylMaterial->price_per_unit : 0,
                    'vinyl_base_price' => $vinylMaterial ? $vinylMaterial->calculatePrice($areaSqm) : 0,
                    'options' => $options,
                    'travel_desc' => $travel_desc,
                    'travel_price' => $travel_price,
                    'price_per_piece' => $calculator->getTotalPrice(),
                    'total_price' => $calculator->getTotalPrice()
                ];
            }
        }
        elseif ($calculator_type === 'letter') {
            $lt_height = floatval($_POST['letter_height'] ?? 0);
            $lt_quantity = isset($_POST['letter_quantity']) && is_numeric($_POST['letter_quantity']) && $_POST['letter_quantity'] > 0 ? intval($_POST['letter_quantity']) : 0;
            $lt_selected_material_id = $_POST['material'] ?? '';
            $lt_selected_options = isset($_POST['options']) ? (is_array($_POST['options']) ? $_POST['options'] : []) : [];
            $lt_travel_type = $_POST['travel_type'] ?? 'none';
            $lt_distance_km = $_POST['distance_km'] ?? '';

            if ($lt_height <= 0 || $lt_quantity <= 0 || empty($lt_selected_material_id)) {
                $response['error'] = "กรุณากรอก ความสูง (>0), จำนวน (>0) และเลือกวัสดุ";
            } else {
                $calculator = new LetterCalculator($priceRuleManager, $optionManager, $materialManager, $priceRounder);
                $calculator->setDimensions(0, $lt_height, $lt_quantity); // width ไม่ใช้, height = ความสูงตัวอักษร
                $letterMaterial = $materialManager->getMaterialById((int)$lt_selected_material_id);
                $calculator->setLetterMaterial($letterMaterial);
                $calculator->setSelectedOptions(array_map('intval', $lt_selected_options));
                $calculator->setTravelInfo($lt_travel_type, is_numeric($lt_distance_km) ? floatval($lt_distance_km) : null);
                $calculator->calculate();

                $options = [];
                foreach ($lt_selected_options as $oid) {
                    $opt = $optionManager->getOptionById((int)$oid);
                    if ($opt) {
                        $options[] = ['name' => $opt->option_name, 'price' => $opt->option_price];
                    }
                }
                $travel_price = 0;
                $travel_desc = 'ไม่รวมค่าเดินทาง';
                foreach ($calculator->getPriceBreakdown() as $comp) {
                    if ($comp['name'] === 'ค่าเดินทาง') {
                        $travel_price = $comp['price'];
                        $travel_desc = $comp['description'];
                    }
                }
                $response['success'] = true;
                $response['results'] = [
                    'height' => $lt_height,
                    'quantity' => $lt_quantity,
                    'material_name' => $letterMaterial ? $letterMaterial->material_name : '',
                    'material_price_pu' => $letterMaterial ? $letterMaterial->price_per_unit : 0,
                    'material_unit' => $letterMaterial ? $letterMaterial->unit : '',
                    'base_price' => null, // ไม่แยก base price ใน OOP version
                    'options' => $options,
                    'travel_desc' => $travel_desc,
                    'travel_price' => $travel_price,
                    'total_price' => $calculator->getTotalPrice()
                ];
            }
        }
        elseif ($calculator_type === 'lightbox') {
            $lb_width = floatval($_POST['lb_width'] ?? 0);
            $lb_height = floatval($_POST['lb_height'] ?? 0);
            $lb_selected_id = $_POST['lightbox_type'] ?? '';
            $lb_selected_options = isset($_POST['lb_options']) ? (is_array($_POST['lb_options']) ? $_POST['lb_options'] : []) : [];
            $lb_travel_type = $_POST['lb_travel_type'] ?? 'none';
            $lb_distance_km = $_POST['lb_distance_km'] ?? '';

            if ($lb_width <= 0 || $lb_height <= 0 || empty($lb_selected_id)) {
                $response['error'] = "กรุณากรอก กว้าง (>0), ยาว (>0) และเลือกประเภทกล่องไฟ";
            } else {
                $calculator = new LightboxCalculator($priceRuleManager, $optionManager, $materialManager, $priceRounder);
                $calculator->setDimensions($lb_width, $lb_height, 1); // กล่องไฟไม่มีจำนวน
                $lightboxType = $materialManager->getMaterialById((int)$lb_selected_id);
                $calculator->setLightboxType($lightboxType);
                $calculator->setSelectedOptions(array_map('intval', $lb_selected_options));
                $calculator->setTravelInfo($lb_travel_type, is_numeric($lb_distance_km) ? floatval($lb_distance_km) : null);
                $calculator->calculate();

                $areaSqm = ($lb_width * $lb_height) / 10000;
                $options = [];
                foreach ($lb_selected_options as $oid) {
                    $opt = $optionManager->getOptionById((int)$oid);
                    if ($opt) {
                        $options[] = ['name' => $opt->option_name, 'price' => $opt->option_price];
                    }
                }
                $travel_price = 0;
                $travel_desc = 'ไม่รวมค่าเดินทาง';
                foreach ($calculator->getPriceBreakdown() as $comp) {
                    if ($comp['name'] === 'ค่าเดินทาง') {
                        $travel_price = $comp['price'];
                        $travel_desc = $comp['description'];
                    }
                }
                $response['success'] = true;
                $response['results'] = [
                    'area' => $areaSqm,
                    'shape' => '', // ไม่ได้คำนวณรูปทรงใน OOP version
                    'width' => $lb_width,
                    'height' => $lb_height,
                    'type_name' => $lightboxType ? $lightboxType->material_name : '',
                    'base_price' => $lightboxType ? $lightboxType->calculatePrice($areaSqm) : 0,
                    'options' => $options,
                    'travel_desc' => $travel_desc,
                    'travel_price' => $travel_price,
                    'total_price' => $calculator->getTotalPrice()
                ];
            }
        }
        else {
            $response['error'] = 'ประเภทการคำนวณไม่รู้จัก';
        }
    } else {
        $response['error'] = 'ไม่ได้ระบุประเภทการคำนวณ';
    }
} catch (Exception $e) {
    $response['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}

echo json_encode($response);
exit;