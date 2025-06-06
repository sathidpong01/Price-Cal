<?php
/**
 * data_fetcher.php
 * ไฟล์สำหรับรวบรวมฟังก์ชันในการดึงข้อมูลที่ใช้ร่วมกันจากฐานข้อมูล
 */

if (!function_exists('getPriceRules')) {
    /**
     * ดึงข้อมูล Price Rules ที่เปิดใช้งานอยู่
     * @param mysqli $conn Object การเชื่อมต่อฐานข้อมูล
     * @return array ['rule_name' => 'rule_value', ...]
     */
    function getPriceRules($conn) {
        $rules = [];
        $sql = "SELECT rule_name, rule_value FROM price_rules WHERE display_in_calculator = 1";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rules[$row['rule_name']] = $row['rule_value'];
            }
        }
        return $rules;
    }
}

if (!function_exists('getAllOptionsByCategory')) {
    /**
     * ดึงข้อมูล Options ทั้งหมดที่เปิดใช้งานอยู่ และจัดกลุ่มตาม Category
     * @param mysqli $conn Object การเชื่อมต่อฐานข้อมูล
     * @return array [ 'CategoryName' => [ ...options... ], ... ]
     */
    function getAllOptionsByCategory($conn) {
        $options = [
            'ทั่วไป' => [], 'สติ๊กเกอร์' => [], 'ผ้าไวนิล' => [],
            'ตัวอักษรโลหะ' => [], 'กล่องไฟ' => []
        ];
        $sql = "SELECT option_id, option_name, option_price, category FROM options WHERE display_in_calculator = 1";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $category_key = $row['category'] ?? 'ทั่วไป';
                if (array_key_exists($category_key, $options)) {
                    $options[$category_key][] = $row;
                } else {
                    $options['ทั่วไป'][] = $row; // Fallback for unknown categories
                }
            }
        }
        return $options;
    }
}

if (!function_exists('getAllMaterialsByType')) {
    /**
     * ดึงข้อมูล Materials ทั้งหมดที่เปิดใช้งานอยู่ และจัดกลุ่มตาม Product Type
     * @param mysqli $conn Object การเชื่อมต่อฐานข้อมูล
     * @return array [ 'ProductType' => [ ...materials... ], ... ]
     */
    function getAllMaterialsByType($conn) {
        $materials = [
            'ตัวอักษรโลหะ' => [], 'กล่องไฟ' => [], 'วัสดุแผ่น' => [],
            'ผ้าไวนิล' => [], 'สติ๊กเกอร์' => []
        ];
        $sql = "SELECT material_id, product_type, material_name, price_per_unit, unit FROM materials WHERE display_in_calculator = 1";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (array_key_exists($row['product_type'], $materials)) {
                    $materials[$row['product_type']][] = $row;
                }
            }
        }
        return $materials;
    }
}
?>