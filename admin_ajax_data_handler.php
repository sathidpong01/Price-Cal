<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/db_connect.php';

// Initialize response structure
$response = ['success' => false, 'data' => null, 'error' => 'Invalid request', 'message' => ''];

$method = $_SERVER["REQUEST_METHOD"];

// --- Handle GET requests for fetching data for modals ---
if ($method === "GET" && isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    $table = '';
    $id_col = '';

    if ($action === 'get_rule_data') { $table = 'price_rules'; $id_col = 'rule_id'; }
    elseif ($action === 'get_material_data') { $table = 'materials'; $id_col = 'material_id'; }
    elseif ($action === 'get_option_data') { $table = 'options'; $id_col = 'option_id'; }
    elseif ($action === 'get_stock_data') { $table = 'stock'; $id_col = 'stock_id'; }

    if ($id > 0 && !empty($table)) {
        $stmt = $conn->prepare("SELECT * FROM {$table} WHERE {$id_col} = ?");
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
    }
}
// --- Handle POST requests for creating, updating, deleting data ---
elseif ($method === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        // --- UPDATE ACTIONS ---
        case 'update_stock_quantity':
            $stock_id = filter_input(INPUT_POST, 'stock_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            if ($stock_id && $quantity !== false && $quantity >= 0) {
                $stmt = $conn->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
                $stmt->bind_param("ii", $quantity, $stock_id);
                if ($stmt->execute()) {
                    $response = [
                        'success' => true,
                        'message' => 'อัปเดตจำนวนสต็อกเรียบร้อย!',
                        'data' => ['stock_id' => $stock_id, 'quantity' => $quantity]
                    ];
                } else { $response['error'] = "Execute ล้มเหลว: " . $stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'ข้อมูล ID หรือ Quantity ไม่ถูกต้อง'; }
            break;

        case 'update_stock':
            $id = filter_input(INPUT_POST, 'stock_id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $conn->prepare("UPDATE stock SET product_name = ?, product_type = ?, quantity = ?, unit = ? WHERE stock_id = ?");
                $stmt->bind_param("ssisi", $_POST['product_name'], $_POST['product_type'], $_POST['quantity'], $_POST['unit'], $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => "อัปเดตสินค้าเรียบร้อย!", 'data' => $_POST];
                } else { $response['error'] = 'Update failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'ข้อมูลไม่ครบถ้วน'; }
            break;

        case 'update_rule':
            $id = filter_input(INPUT_POST, 'rule_id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $conn->prepare("UPDATE price_rules SET rule_name = ?, rule_value = ?, rule_unit = ? WHERE rule_id = ?");
                $stmt->bind_param("sdsi", $_POST['rule_name'], $_POST['rule_value'], $_POST['rule_unit'], $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => "อัปเดตกฎราคาเรียบร้อย!", 'data' => $_POST];
                } else { $response['error'] = 'Update failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'ข้อมูลไม่ครบถ้วน'; }
            break;

        case 'update_material':
            $id = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $conn->prepare("UPDATE materials SET product_type = ?, material_name = ?, price_per_unit = ?, unit = ? WHERE material_id = ?");
                $stmt->bind_param("ssdsi", $_POST['material_type'], $_POST['material_name'], $_POST['material_price'], $_POST['material_unit'], $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => "อัปเดตวัสดุเรียบร้อย!", 'data' => array_merge($_POST, ['price_per_unit' => $_POST['material_price']])];
                } else { $response['error'] = 'Update failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'ข้อมูลไม่ครบถ้วน'; }
            break;

        case 'update_option':
            $id = filter_input(INPUT_POST, 'option_id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $conn->prepare("UPDATE options SET option_name = ?, option_price = ?, category = ? WHERE option_id = ?");
                $stmt->bind_param("sdsi", $_POST['option_name'], $_POST['option_price'], $_POST['option_category'], $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => "อัปเดตออปชันเรียบร้อย!", 'data' => array_merge($_POST, ['category' => $_POST['option_category']])];
                } else { $response['error'] = 'Update failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'ข้อมูลไม่ครบถ้วน'; }
            break;

        // --- DELETE ACTIONS ---
        case 'delete_rule':
        case 'delete_material':
        case 'delete_option':
        case 'delete_stock':
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $type = str_replace('delete_', '', $action);
            $table = ''; $id_col = '';
            
            if ($type === 'rule') { $table = 'price_rules'; $id_col = 'rule_id'; }
            elseif ($type === 'material') { $table = 'materials'; $id_col = 'material_id'; }
            elseif ($type === 'option') { $table = 'options'; $id_col = 'option_id'; }
            elseif ($type === 'stock') { $table = 'stock'; $id_col = 'stock_id'; }

            if ($id && !empty($table)) {
                $stmt = $conn->prepare("DELETE FROM {$table} WHERE {$id_col} = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => "ลบข้อมูลเรียบร้อยแล้ว!"];
                } else { $response['error'] = 'Delete failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'Invalid ID for deletion.'; }
            break;

        // --- DISPLAY STATUS ACTION ---
        case 'update_display_status':
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $type = $_POST['type'] ?? '';
            $visible = filter_input(INPUT_POST, 'visible', FILTER_VALIDATE_INT);
            $table = ''; $id_col = '';

            if ($type === 'rule') { $table = 'price_rules'; $id_col = 'rule_id'; }
            elseif ($type === 'material') { $table = 'materials'; $id_col = 'material_id'; }
            elseif ($type === 'option') { $table = 'options'; $id_col = 'option_id'; }

            if ($id && !empty($table) && $visible !== null) {
                $stmt = $conn->prepare("UPDATE {$table} SET display_in_calculator = ? WHERE {$id_col} = ?");
                $stmt->bind_param("ii", $visible, $id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                } else { $response['error'] = 'Update failed: '.$stmt->error; }
                $stmt->close();
            } else { $response['error'] = 'Invalid data for display status update.'; }
            break;
        
        default:
            $response['error'] = "Action (POST) ไม่รู้จัก: " . htmlspecialchars($action);
            break;
    }
}

$conn->close();
echo json_encode($response);
exit;