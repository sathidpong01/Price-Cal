<?php
// admin_ajax_data_handler.php — Optimized (No @ suppression, better error handling)

// 1. Clear Output Buffer
if (function_exists('ob_get_level')) {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// 2. Error Handling Setup
// Disable display_errors for production (JSON response shouldn't have HTML errors mixed in)
ini_set('display_errors', '0');
// Report all errors
error_reporting(E_ALL);

// Custom Error Handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Skip if error reporting is suppressed (e.g. by @ operator, though we shouldn't use it)
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => "PHP Error: [$errno] $errstr in $errfile:$errline"
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Custom Exception Handler
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => "Exception: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

header('Content-Type: application/json; charset=utf-8');

// 3. Include Dependencies
$db_connect_path = __DIR__ . '/includes/db_connect.php';
if (!file_exists($db_connect_path)) {
    json_err("Critical Error: db_connect.php not found at $db_connect_path", 500);
}
require_once $db_connect_path;

$api_helpers_path = __DIR__ . '/includes/api_helpers.php';
if (file_exists($api_helpers_path)) {
    include_once $api_helpers_path;
}

// 4. Helper Functions
if (!function_exists('json_start')) {
    function json_start(){ 
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8'); 
        }
    }
}

if (!function_exists('json_ok')) {
    function json_ok($data=null, $message=null, $code=200){
        json_start();
        if ($code !== 200) http_response_code($code);
        echo json_encode(['success'=>true,'message'=>$message,'data'=>$data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('json_err')) {
    function json_err($message='เกิดข้อผิดพลาด', $code=400){
        json_start();
        http_response_code($code);
        echo json_encode(['success'=>false,'error'=>$message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function parse_num($s, $default=0.0){
    if ($s === null) return (float)$default;
    $s = str_replace([',',' '], '', (string)$s);
    return is_numeric($s) ? (float)$s : (float)$default;
}

function get_row($conn, $table, $id_col, $id, $cols='*'){
    $sql = "SELECT $cols FROM $table WHERE $id_col=? LIMIT 1";
    $st = $conn->prepare($sql); 
    if(!$st) {
        throw new Exception('Prepare failed: '.$conn->error);
    }
    $st->bind_param('i', $id); 
    $st->execute();
    $res = $st->get_result(); 
    $row = $res ? $res->fetch_assoc() : null;
    $st->close(); 
    return $row ?: null;
}

function cast_fields(&$row, $floats=[], $ints=[]){
    foreach ($floats as $k) if (isset($row[$k])) $row[$k] = (float)$row[$k];
    foreach ($ints as $k)   if (isset($row[$k])) $row[$k] = (int)$row[$k];
}

function table_id_for_type($type){
    switch ($type) {
        case 'rule':     return ['price_rules','rule_id'];
        case 'material': return ['materials','material_id'];
        case 'option':   return ['options','option_id'];
        case 'stock':    return ['stock','stock_id'];
        default:         return [null,null];
    }
}

// 5. Main Logic
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // ---------------- GET ----------------
    if ($method === 'GET') {
        if ($action === 'get_price_rule') $action = 'get_rule_data';
        if ($action === 'get_material')   $action = 'get_material_data';
        if ($action === 'get_option')     $action = 'get_option_data';
        if ($action === 'get_stock')      $action = 'get_stock_data';

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) json_err('รหัสไม่ถูกต้อง', 422);

        if ($action === 'get_rule_data') {
            $row = get_row($conn,'price_rules','rule_id',$id,'rule_id, rule_name, rule_value, rule_unit, display_in_calculator');
            if (!$row) json_err('ไม่พบกฎราคา',404);
            cast_fields($row, ['rule_value'], ['rule_id','display_in_calculator']);
            json_ok($row);
        }
        if ($action === 'get_material_data') {
            $row = get_row($conn,'materials','material_id',$id,'material_id, product_type, product_type_id, material_name, price_per_unit, unit, display_in_calculator');
            if (!$row) json_err('ไม่พบวัสดุ',404);
            cast_fields($row, ['price_per_unit'], ['material_id','product_type_id','display_in_calculator']);
            json_ok($row);
        }
        if ($action === 'get_option_data') {
            $row = get_row($conn,'options','option_id',$id,'option_id, option_name, option_price, category, category_id, display_in_calculator');
            if (!$row) json_err('ไม่พบออปชัน',404);
            cast_fields($row, ['option_price'], ['option_id','category_id','display_in_calculator']);
            json_ok($row);
        }
        if ($action === 'get_stock_data') {
            $row = get_row($conn,'stock','stock_id',$id,'stock_id, product_name, product_type, quantity, unit');
            if (!$row) json_err('ไม่พบสต็อก',404);
            cast_fields($row, [], ['stock_id','quantity']);
            json_ok($row);
        }

        json_err('Action (GET) ไม่รู้จัก: '.htmlspecialchars($action), 400);
    }

    // ---------------- POST ----------------
    // UPDATE: rule
    if ($action === 'update_price_rule' || $action === 'update_rule') {
        $id   = intval($_POST['rule_id'] ?? 0);
        $name = trim($_POST['rule_name'] ?? '');
        $val  = parse_num($_POST['rule_value'] ?? '0');
        $unit = trim($_POST['rule_unit'] ?? '');
        if ($id<=0 || $name==='' || $unit==='') json_err('ข้อมูลไม่ครบถ้วน', 422);

        $st = $conn->prepare("UPDATE price_rules SET rule_name=?, rule_value=?, rule_unit=? WHERE rule_id=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('sdsi', $name, $val, $unit, $id);
        $ok = $st->execute(); $err = $st->error; $st->close();
        if (!$ok) json_err('Update failed: '.$err, 500);

        $row = get_row($conn,'price_rules','rule_id',$id,'rule_id, rule_name, rule_value, rule_unit, display_in_calculator');
        cast_fields($row, ['rule_value'], ['rule_id','display_in_calculator']);
        json_ok($row, 'อัปเดตกฎราคาเรียบร้อย!');
    }

    // UPDATE: material
    if ($action === 'update_material') {
        $id    = intval($_POST['material_id'] ?? 0);
        $type  = trim($_POST['material_type'] ?? '');
        $name  = trim($_POST['material_name'] ?? '');
        $price = parse_num($_POST['material_price'] ?? '0');
        $unit  = trim($_POST['material_unit'] ?? '');
        if ($id<=0 || $name==='' || $unit==='') json_err('ข้อมูลไม่ครบถ้วน', 422);

        $st = $conn->prepare("UPDATE materials SET product_type=?, material_name=?, price_per_unit=?, unit=? WHERE material_id=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('ssdsi', $type, $name, $price, $unit, $id);
        $ok = $st->execute(); $err = $st->error; $st->close();
        if (!$ok) json_err('Update failed: '.$err, 500);

        $row = get_row($conn,'materials','material_id',$id,'material_id, product_type, product_type_id, material_name, price_per_unit, unit, display_in_calculator');
        cast_fields($row, ['price_per_unit'], ['material_id','product_type_id','display_in_calculator']);
        json_ok($row, 'อัปเดตวัสดุเรียบร้อย!');
    }

    // UPDATE: option
    if ($action === 'update_option') {
        $id    = intval($_POST['option_id'] ?? 0);
        $name  = trim($_POST['option_name'] ?? '');
        $price = parse_num($_POST['option_price'] ?? '0');
        $cat   = trim($_POST['option_category'] ?? '');
        if ($id<=0 || $name==='') json_err('ข้อมูลไม่ครบถ้วน', 422);

        $st = $conn->prepare("UPDATE options SET option_name=?, option_price=?, category=? WHERE option_id=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('sdsi', $name, $price, $cat, $id);
        $ok = $st->execute(); $err = $st->error; $st->close();
        if (!$ok) json_err('Update failed: '.$err, 500);

        $row = get_row($conn,'options','option_id',$id,'option_id, option_name, option_price, category, category_id, display_in_calculator');
        cast_fields($row, ['option_price'], ['option_id','category_id','display_in_calculator']);
        json_ok($row, 'อัปเดตออปชันเรียบร้อย!');
    }

    // UPDATE: stock qty
    if ($action === 'update_stock_quantity') {
        $id  = intval($_POST['stock_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? -1);
        if ($id<=0 || $qty<0) json_err('ข้อมูล ID หรือ Quantity ไม่ถูกต้อง', 422);

        $st = $conn->prepare("UPDATE stock SET quantity=? WHERE stock_id=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('ii', $qty, $id);
        $ok = $st->execute(); $err=$st->error; $st->close();
        if (!$ok) json_err('Update failed: '.$err,500);

        $row = ['stock_id'=>$id,'quantity'=>$qty];
        cast_fields($row, [], ['stock_id','quantity']);
        json_ok($row, 'อัปเดตจำนวนสต็อกเรียบร้อย!');
    }

    // UPDATE: stock full
    if ($action === 'update_stock') {
        $id   = intval($_POST['stock_id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $type = trim($_POST['product_type'] ?? '');
        $qty  = intval($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        if ($id<=0 || $name==='' || $unit==='') json_err('ข้อมูลไม่ครบถ้วน', 422);

        $st = $conn->prepare("UPDATE stock SET product_name=?, product_type=?, quantity=?, unit=? WHERE stock_id=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('ssisi', $name, $type, $qty, $unit, $id);
        $ok = $st->execute(); $err=$st->error; $st->close();
        if (!$ok) json_err('Update failed: '.$err,500);

        $row = get_row($conn,'stock','stock_id',$id,'stock_id, product_name, product_type, quantity, unit');
        cast_fields($row, [], ['stock_id','quantity']);
        json_ok($row, 'อัปเดตสินค้าเรียบร้อย!');
    }

    // TOGGLE display
    if ($action === 'toggle_rule_display' || $action === 'toggle_price_rule_display' || $action === 'update_display_status') {
        // Handle generic update_display_status
        if ($action === 'update_display_status') {
            $type = $_POST['type'] ?? '';
            $id = intval($_POST['id'] ?? 0);
            $visible = intval($_POST['visible'] ?? 0);
            
            list($table, $id_col) = table_id_for_type($type);
            if (!$table) json_err('Invalid type', 400);
            
            $st = $conn->prepare("UPDATE $table SET display_in_calculator=? WHERE $id_col=?");
            if(!$st) throw new Exception('Prepare failed: '.$conn->error);
            $st->bind_param('ii', $visible, $id);
            $ok = $st->execute(); $err=$st->error; $st->close();
            if (!$ok) json_err('Update failed: '.$err, 500);
            
            json_ok(['id'=>$id, 'visible'=>$visible], 'อัปเดตสถานะเรียบร้อย');
        } else {
            // Legacy specific toggle
            $id = intval($_POST['rule_id'] ?? 0);
            $display = isset($_POST['display']) ? intval($_POST['display']) : null;
            if ($id<=0 || $display===null) json_err('ข้อมูลไม่ครบถ้วน', 422);

            $st = $conn->prepare("UPDATE price_rules SET display_in_calculator=? WHERE rule_id=?");
            if(!$st) throw new Exception('Prepare failed: '.$conn->error);
            $st->bind_param('ii', $display, $id);
            $ok = $st->execute(); $err=$st->error; $st->close();
            if (!$ok) json_err('Update failed: '.$err,500);

            $row = ['rule_id'=>$id,'display_in_calculator'=>$display];
            cast_fields($row, [], ['rule_id','display_in_calculator']);
            json_ok($row, 'อัปเดตการแสดงผลแล้ว');
        }
    }

    // DELETE (physical)
    if ($action === 'delete_rule' || $action === 'delete_material' || $action === 'delete_option' || $action === 'delete_stock') {
        $id = intval($_POST['id'] ?? 0);
        if ($id<=0) json_err('รหัสไม่ถูกต้อง', 422);
        $type = str_replace('delete_', '', $action);
        list($table, $id_col) = table_id_for_type($type);
        if (!$table) json_err('ประเภทไม่ถูกต้อง', 400);

        $st = $conn->prepare("DELETE FROM $table WHERE $id_col=?");
        if(!$st) throw new Exception('Prepare failed: '.$conn->error);
        $st->bind_param('i', $id);
        $ok = $st->execute(); $err=$st->error; $st->close();
        if (!$ok) json_err('Delete failed: '.$err,500);

        $row = ['id'=>$id,'type'=>$type];
        cast_fields($row, [], ['id']);
        json_ok($row, 'ลบเรียบร้อย');
    }

    json_err('Action (POST) ไม่รู้จัก: '.htmlspecialchars($action), 400);

} catch (Exception $e) {
    json_err('Server Exception: ' . $e->getMessage(), 500);
} catch (Error $e) {
    json_err('Server Error: ' . $e->getMessage(), 500);
}
