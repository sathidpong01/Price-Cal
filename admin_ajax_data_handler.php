<?php
header('Content-Type: application/json; charset=utf-8');
include 'includes/db_connect.php'; 

$response = ['success' => false, 'data' => null, 'error' => 'Invalid request', 'message' => '']; 

// --- GET request handler ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if ($id > 0) {
        $sql = "";
        if ($action == 'get_rule_data') {
            $sql = "SELECT * FROM price_rules WHERE rule_id = ?";
        } elseif ($action == 'get_material_data') {
            $sql = "SELECT * FROM materials WHERE material_id = ?";
        } elseif ($action == 'get_option_data') {
            $sql = "SELECT * FROM options WHERE option_id = ?";
        } elseif ($action == 'get_stock_data') {
            $sql = "SELECT * FROM stock WHERE stock_id = ?";
        }

        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                    $response['error'] = '';
                } else {
                    $response['error'] = 'ไม่พบข้อมูล';
                }
                $stmt->close();
            } else {
                $response['error'] = 'เกิดข้อผิดพลาดในการเตรียม SQL (GET)';
            }
        }
    }
} 
// --- POST request handler ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- (Full Update) update_stock handler from Modal ---
    if ($action == 'update_stock') {
        $stock_id = isset($_POST['stock_id']) ? intval($_POST['stock_id']) : 0;
        $product_name = $_POST['product_name'] ?? '';
        $product_type = $_POST['product_type'] ?? '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        $unit = $_POST['unit'] ?? '';

        if ($stock_id > 0 && !empty($product_name) && !empty($product_type) && $quantity >= 0 && !empty($unit)) {
            $sql_update = "UPDATE stock SET product_name = ?, product_type = ?, quantity = ?, unit = ? WHERE stock_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("ssisi", $product_name, $product_type, $quantity, $unit, $stock_id);
                if ($stmt_update->execute()) {
                    $response['success'] = true;
                    $response['message'] = "อัปเดตสินค้า '" . htmlspecialchars($product_name) . "' เรียบร้อย!";
                    $response['data'] = [
                        'stock_id' => $stock_id,
                        'product_name' => $product_name,
                        'product_type' => $product_type,
                        'quantity' => $quantity,
                        'unit' => $unit
                    ];
                } else {
                    $response['error'] = "ผิดพลาด อัปเดตสินค้า: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $response['error'] = "ผิดพลาด SQL อัปเดตสินค้า: " . $conn->error;
            }
        } else {
            $response['error'] = "กรุณากรอกข้อมูลสินค้าให้ครบถ้วนและถูกต้อง!";
        }
    }

    // --- (Quantity Only Update) update_stock_quantity handler from inline edit ---
    // --- ADDED START ---
    elseif ($action == 'update_stock_quantity') {
        $stock_id = isset($_POST['stock_id']) ? intval($_POST['stock_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : -1; // -1 to detect if not sent

        if ($stock_id > 0 && $quantity >= 0) {
            $sql_update = "UPDATE stock SET quantity = ? WHERE stock_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("ii", $quantity, $stock_id);
                if ($stmt_update->execute()) {
                    $response['success'] = true;
                    $response['message'] = "อัปเดตจำนวนสต็อกเรียบร้อย!";
                    $response['data'] = ['stock_id' => $stock_id, 'quantity' => $quantity];
                } else {
                    $response['error'] = "ผิดพลาด อัปเดตจำนวนสต็อก: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $response['error'] = "ผิดพลาด SQL อัปเดตจำนวนสต็อก: " . $conn->error;
            }
        } else {
            $response['error'] = "ข้อมูล ID หรือ Quantity ไม่ถูกต้อง!";
        }
    }
    // --- ADDED END ---

    // --- update_rule handler ---
    elseif ($action == 'update_rule') {
        $rule_id = isset($_POST['rule_id']) ? intval($_POST['rule_id']) : 0;
        $rule_name = $_POST['rule_name'] ?? '';
        $rule_value = $_POST['rule_value'] ?? '';
        $rule_unit = $_POST['rule_unit'] ?? '';

        if ($rule_id > 0 && !empty($rule_name) && is_numeric($rule_value) && $rule_value >= 0 && !empty($rule_unit)) {
            $sql_update = "UPDATE price_rules SET rule_name = ?, rule_value = ?, rule_unit = ? WHERE rule_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("sdsi", $rule_name, $rule_value, $rule_unit, $rule_id);
                if ($stmt_update->execute()) {
                    $response['success'] = true;
                    $response['message'] = "อัปเดตกฎราคา '" . htmlspecialchars($rule_name) . "' เรียบร้อย!";
                    $response['data'] = ['rule_id' => $rule_id, 'rule_name' => $rule_name, 'rule_value' => $rule_value, 'rule_unit' => $rule_unit];
                } else {
                    $response['error'] = "ผิดพลาด อัปเดตกฎราคา: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $response['error'] = "ผิดพลาด SQL อัปเดตกฎราคา: " . $conn->error;
            }
        } else {
            $response['error'] = "กรุณากรอกข้อมูลกฎราคาให้ครบถ้วนและถูกต้อง!";
        }
    } 
    
    // --- update_material handler ---
    elseif ($action == 'update_material') {
        $mat_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;
        $mat_type = $_POST['material_type'] ?? '';
        $mat_name = $_POST['material_name'] ?? '';
        $mat_price = $_POST['material_price'] ?? '';
        $mat_unit = $_POST['material_unit'] ?? '';

        if ($mat_id > 0 && !empty($mat_type) && !empty($mat_name) && is_numeric($mat_price) && $mat_price >= 0 && !empty($mat_unit)) {
            $sql_update = "UPDATE materials SET product_type = ?, material_name = ?, price_per_unit = ?, unit = ? WHERE material_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("ssdsi", $mat_type, $mat_name, $mat_price, $mat_unit, $mat_id);
                if ($stmt_update->execute()) {
                    $response['success'] = true;
                    $response['message'] = "อัปเดตวัสดุ '" . htmlspecialchars($mat_name) . "' เรียบร้อย!";
                    $response['data'] = ['material_id' => $mat_id, 'product_type' => $mat_type, 'material_name' => $mat_name, 'price_per_unit' => $mat_price, 'unit' => $mat_unit];
                } else {
                    $response['error'] = "ผิดพลาด อัปเดตวัสดุ: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $response['error'] = "ผิดพลาด SQL อัปเดตวัสดุ: " . $conn->error;
            }
        } else {
            $response['error'] = "กรุณากรอกข้อมูลวัสดุให้ครบถ้วนและถูกต้อง!";
        }
    } 
    
    // --- update_option handler ---
    elseif ($action == 'update_option') {
        $opt_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
        $opt_name = $_POST['option_name'] ?? '';
        $opt_price = $_POST['option_price'] ?? '';
        $opt_category = $_POST['option_category'] ?? 'ทั่วไป';

        if ($opt_id > 0 && !empty($opt_name) && is_numeric($opt_price) && $opt_price >= 0 && !empty($opt_category)) {
            $sql_update = "UPDATE options SET option_name = ?, option_price = ?, category = ? WHERE option_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("sdsi", $opt_name, $opt_price, $opt_category, $opt_id);
                if ($stmt_update->execute()) {
                    $response['success'] = true;
                    $response['message'] = "อัปเดตออปชัน '" . htmlspecialchars($opt_name) . "' เรียบร้อย!";
                    $response['data'] = ['option_id' => $opt_id, 'option_name' => $opt_name, 'option_price' => $opt_price, 'category' => $opt_category];
                } else { $response['error'] = "ผิดพลาด อัปเดตออปชัน: " . $stmt_update->error; }
                $stmt_update->close();
            } else { $response['error'] = "ผิดพลาด SQL อัปเดตออปชัน: " . $conn->error; }
        } else { $response['error'] = "กรุณากรอกข้อมูลออปชันให้ครบถ้วนและถูกต้อง!"; }
    }
    
    // --- delete handlers ---
    elseif (strpos($action, 'delete_') === 0) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $table_name = ''; $id_column = ''; $item_type = '';

        if ($action == 'delete_rule') { $table_name = 'price_rules'; $id_column = 'rule_id'; $item_type = 'กฎราคา'; } 
        elseif ($action == 'delete_material') { $table_name = 'materials'; $id_column = 'material_id'; $item_type = 'วัสดุ'; } 
        elseif ($action == 'delete_option') { $table_name = 'options'; $id_column = 'option_id'; $item_type = 'ออปชัน'; }
        elseif ($action == 'delete_stock') { $table_name = 'stock'; $id_column = 'stock_id'; $item_type = 'สินค้าในสต็อก'; }

        if ($id > 0 && !empty($table_name)) {
            $sql_delete = "DELETE FROM {$table_name} WHERE {$id_column} = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $id);
                if ($stmt_delete->execute()) {
                    $response['success'] = true;
                    $response['message'] = "ลบ{$item_type}เรียบร้อยแล้ว!";
                } else {
                    $response['error'] = "เกิดข้อผิดพลาดในการลบ{$item_type}: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            } else {
                $response['error'] = "เกิดข้อผิดพลาด SQL ในการลบ{$item_type}: " . $conn->error;
            }
        } else {
            $response['error'] = "ข้อมูลไม่ถูกต้องสำหรับการลบ{$item_type}";
        }
    }
    
    // --- update_display_status handler ---
    elseif ($action == 'update_display_status') {
        $type = $_POST['type'] ?? '';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $visible = isset($_POST['visible']) ? intval($_POST['visible']) : 0;

        $table = ''; $id_column = ''; $name_column = '';
        if ($type == 'rule') { $table = 'price_rules'; $id_column = 'rule_id'; $name_column = 'rule_name'; } 
        elseif ($type == 'material') { $table = 'materials'; $id_column = 'material_id'; $name_column = 'material_name'; } 
        elseif ($type == 'option') { $table = 'options'; $id_column = 'option_id'; $name_column = 'option_name'; }

        if ($id > 0 && !empty($table)) {
            $sql = "UPDATE {$table} SET display_in_calculator = ? WHERE {$id_column} = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $visible, $id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $sql_select_name = "SELECT {$name_column} FROM {$table} WHERE {$id_column} = ?";
                    $stmt_name = $conn->prepare($sql_select_name);
                    if ($stmt_name) {
                        $stmt_name->bind_param("i", $id);
                        $stmt_name->execute();
                        $result_name = $stmt_name->get_result();
                        if ($result_name->num_rows === 1) {
                            $row_name = $result_name->fetch_assoc();
                            $response['name'] = $row_name[$name_column];
                        }
                        $stmt_name->close();
                    }
                } else {
                     $response['error'] = "ผิดพลาดในการอัปเดตฐานข้อมูล: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['error'] = "ผิดพลาดในการเตรียม SQL: " . $conn->error;
            }
        } else {
            $response['error'] = "ข้อมูลไม่ถูกต้องสำหรับการอัปเดตสถานะ (Type: {$type}, ID: {$id})";
        }
    }

    // --- Fallback for unknown actions ---
    else {
        $response['error'] = "Action (POST) ไม่รู้จัก: " . htmlspecialchars($action);
    }
}


$conn->close();
echo json_encode($response);
exit;
?>