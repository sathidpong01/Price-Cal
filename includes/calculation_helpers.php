<?php
/**
 * includes/calculation_helpers.php
 * รวมฟังก์ชันคำนวณที่ใช้ซ้ำๆ เพื่อลดความซ้ำซ้อนของโค้ด (DRY)
 */

/**
 * คำนวณค่าเดินทาง
 * @param string $travelType ประเภทการเดินทาง ('none', 'in_city', 'out_city')
 * @param float $distance ระยะทาง (กม.)
 * @param float $costInCity ค่าเดินทางในเมือง
 * @param float $costPerKm ค่าเดินทางนอกเมืองต่อ กม.
 * @return array ['cost' => float, 'description' => string]
 */
function calculateTravelCost($travelType, $distance, $costInCity, $costPerKm) {
    $cost = 0;
    $description = 'ไม่รวมค่าเดินทาง';

    if ($travelType === 'in_city') {
        $cost = $costInCity;
        $description = 'ในเมือง';
    } elseif ($travelType === 'out_city') {
        if (is_numeric($distance) && $distance > 0) {
            $cost = $distance * $costPerKm;
            $description = "นอกเมือง ({$distance} กม.)";
        }
    }

    return ['cost' => $cost, 'description' => $description];
}

/**
 * คำนวณราคาออปชันเสริม
 * @param array $selectedOptionIds ID ของออปชันที่เลือก
 * @param array $allOptionsList รายการออปชันทั้งหมด (ที่มี 'option_id', 'option_name', 'option_price')
 * @return array ['total_price' => float, 'details' => array]
 */
function calculateOptionsPrice($selectedOptionIds, $allOptionsList) {
    $totalPrice = 0;
    $details = [];

    if (!is_array($selectedOptionIds)) {
        return ['total_price' => 0, 'details' => []];
    }

    // สร้าง Map เพื่อให้ค้นหาเร็วขึ้น (ถ้า list ใหญ่)
    // แต่สำหรับ list เล็กๆ loop ธรรมดาก็พอ
    foreach ($allOptionsList as $opt) {
        if (in_array($opt['option_id'], $selectedOptionIds)) {
            $totalPrice += $opt['option_price'];
            $details[] = [
                'name' => $opt['option_name'],
                'price' => $opt['option_price']
            ];
        }
    }

    return ['total_price' => $totalPrice, 'details' => $details];
}

/**
 * หาข้อมูลวัสดุจาก ID
 * @param int|string $materialId ID ของวัสดุ
 * @param array $materialsList รายการวัสดุทั้งหมด
 * @return array|null ข้อมูลวัสดุ หรือ null ถ้าไม่พบ
 */
function findMaterial($materialId, $materialsList) {
    foreach ($materialsList as $mat) {
        if ($mat['material_id'] == $materialId) {
            return $mat;
        }
    }
    return null;
}
?>
