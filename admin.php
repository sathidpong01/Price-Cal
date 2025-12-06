<?php
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include 'includes/db_connect.php';

$message = '';
$error = '';

    if (!empty($table_name)) {
        $sql_delete_get = "DELETE FROM {$table_name} WHERE {$id_column} = ?";
        $stmt_delete_get = $conn->prepare($sql_delete_get);
        if ($stmt_delete_get) {
            $stmt_delete_get->bind_param("i", $delete_id);
            if (!$stmt_delete_get->execute()) {
                $error .= "เกิดข้อผิดพลาดในการลบ{$item_type} (GET): " . $stmt_delete_get->error . "<br>";
            }
            $stmt_delete_get->close();
            if (empty($error)) {
                header("Location: admin.php?msg={$redirect_msg}{$section_anchor}");
                exit;
            }
        } else {
            $error .= "เกิดข้อผิดพลาด SQL ลบ{$item_type} (GET): " . $conn->error . "<br>";
        }
    }


// ส่วนจัดการการกระทำ (Actions) - เพิ่ม (รับข้อมูล POST จากฟอร์มหลัก)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- เพิ่มกฎราคา ---
    if (isset($_POST['add_rule'])) {
        $rule_name = $_POST['add_rule_name'];
        $rule_value = $_POST['add_rule_value'];
        $rule_unit = $_POST['add_rule_unit'];
        if (!empty($rule_name) && is_numeric($rule_value) && $rule_value >= 0 && !empty($rule_unit)) {
            $sql_add = "INSERT INTO price_rules (rule_name, rule_value, rule_unit) VALUES (?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) {
                $stmt_add->bind_param("sds", $rule_name, $rule_value, $rule_unit);
                if ($stmt_add->execute()) {
                    $message .= "เพิ่มกฎราคา '" . htmlspecialchars($rule_name) . "' เรียบร้อย!<br>";
                } else {
                    $error .= "ผิดพลาด เพิ่มกฎราคา: " . $stmt_add->error . "<br>";
                }
                $stmt_add->close();
            } else {
                $error .= "ผิดพลาด SQL เพิ่มกฎราคา<br>";
            }
        } else {
            $error .= "กรุณากรอกข้อมูลกฎราคาใหม่ให้ครบ!<br>";
        }
    }

    // --- เพิ่มออปชัน ---
    if (isset($_POST['add_option'])) {
        $opt_name = $_POST['add_option_name'];
        $opt_price = $_POST['add_option_price'];
        $opt_category = $_POST['add_option_category'];
        if (!empty($opt_name) && is_numeric($opt_price) && $opt_price >= 0 && !empty($opt_category)) {
            $sql_add = "INSERT INTO options (option_name, option_price, category) VALUES (?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) {
                $stmt_add->bind_param("sds", $opt_name, $opt_price, $opt_category);
                if ($stmt_add->execute()) { $message .= "เพิ่มออปชัน '" . htmlspecialchars($opt_name) . "' เรียบร้อย!<br>"; }
                else { $error .= "ผิดพลาด เพิ่มออปชัน: " . $stmt_add->error . "<br>"; }
                $stmt_add->close();
            } else { $error .= "ผิดพลาด SQL เพิ่มออปชัน<br>"; }
        } else { $error .= "กรุณากรอกข้อมูลออปชันใหม่ให้ครบ!<br>"; }
    }

    // --- เพิ่มวัสดุ ---
    if (isset($_POST['add_material'])) {
        $mat_type = $_POST['add_material_type'];
        $mat_name = $_POST['add_material_name'];
        $mat_price = $_POST['add_material_price'];
        $mat_unit = $_POST['add_material_unit'];
        if (!empty($mat_type) && !empty($mat_name) && is_numeric($mat_price) && $mat_price >= 0 && !empty($mat_unit)) {
            $sql_add = "INSERT INTO materials (product_type, material_name, price_per_unit, unit) VALUES (?, ?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) {
                $stmt_add->bind_param("ssds", $mat_type, $mat_name, $mat_price, $mat_unit);
                if ($stmt_add->execute()) {
                    $message .= "เพิ่มวัสดุ '" . htmlspecialchars($mat_name) . "' เรียบร้อย!<br>";
                } else {
                    $error .= "ผิดพลาด เพิ่มวัสดุ: " . $stmt_add->error . "<br>";
                }
                $stmt_add->close();
            } else {
                $error .= "ผิดพลาด SQL เพิ่มวัสดุ<br>";
            }
        } else {
            $error .= "กรุณากรอกข้อมูลวัสดุใหม่ให้ครบ!<br>";
        }
    }

    // --- เพิ่มสินค้าในสต็อก ---
    if (isset($_POST['add_stock_item'])) {
        $stock_product_name = $_POST['add_stock_product_name'];
        $stock_product_type = $_POST['add_stock_product_type'];
        $stock_quantity = $_POST['add_stock_quantity'];
        $stock_unit = $_POST['add_stock_unit'];

        if (!empty($stock_product_name) && !empty($stock_product_type) && is_numeric($stock_quantity) && $stock_quantity >= 0 && !empty($stock_unit)) {
            $sql_add_stock = "INSERT INTO stock (product_name, product_type, quantity, unit) VALUES (?, ?, ?, ?)";
            $stmt_add_stock = $conn->prepare($sql_add_stock);
            if ($stmt_add_stock) {
                $stmt_add_stock->bind_param("ssis", $stock_product_name, $stock_product_type, $stock_quantity, $stock_unit);
                if ($stmt_add_stock->execute()) {
                    $message .= "เพิ่มสินค้า '" . htmlspecialchars($stock_product_name) . "' ในสต็อกเรียบร้อย!<br>";
                } else {
                    $error .= "ผิดพลาดในการเพิ่มสินค้าในสต็อก: " . $stmt_add_stock->error . "<br>";
                }
                $stmt_add_stock->close();
            } else {
                $error .= "ผิดพลาด SQL ในการเพิ่มสินค้าในสต็อก: " . $conn->error . "<br>";
            }
        } else {
            $error .= "กรุณากรอกข้อมูลสินค้าในสต็อกให้ครบถ้วนและถูกต้อง!<br>";
        }
    }
}

// ส่วนดึงข้อมูล (Fetch Data) สำหรับแสดงผลในตาราง
$price_rules_list = [];
$sql_select_rules = "SELECT rule_id, rule_name, rule_value, rule_unit, display_in_calculator FROM price_rules ORDER BY rule_id";
$result_rules = $conn->query($sql_select_rules);
if ($result_rules) { while ($row_rule = $result_rules->fetch_assoc()) { $price_rules_list[] = $row_rule; } } else { $error .= "ไม่สามารถดึงข้อมูลกฎราคาได้<br>"; }

$materials_list_admin = [];
$sql_select_mats = "SELECT material_id, product_type, material_name, price_per_unit, unit, display_in_calculator FROM materials ORDER BY product_type, material_name";
$result_mats = $conn->query($sql_select_mats);
if ($result_mats) { while ($row_mat = $result_mats->fetch_assoc()) { $materials_list_admin[] = $row_mat; } } else { $error .= "ไม่สามารถดึงข้อมูลวัสดุได้<br>"; }


$options_list_admin = [];
// แก้ไข SQL ให้ดึง category และ display_in_calculator มาด้วย (ถ้ายังไม่ได้ทำ)
$sql_select_opts = "SELECT option_id, option_name, option_price, category, display_in_calculator FROM options ORDER BY category, option_name";
$result_opts = $conn->query($sql_select_opts);
if ($result_opts) {
    while ($row_opt = $result_opts->fetch_assoc()) {
        $options_list_admin[] = $row_opt;
    }
} else {
    $error .= "ไม่สามารถดึงข้อมูลออปชันได้<br>";
}

$existing_product_types = [];
$sql_types = "SELECT DISTINCT product_type FROM materials";
$result_types = $conn->query($sql_types);
if($result_types){ while($row_type = $result_types->fetch_assoc()){ $existing_product_types[] = $row_type['product_type']; } }

$default_types = ['ตัวอักษรโลหะ', 'กล่องไฟ', 'วัสดุแผ่น', 'ผ้าไวนิล', 'สติ๊กเกอร์'];
foreach ($default_types as $dt) {
    if (!in_array($dt, $existing_product_types)) {
        $existing_product_types[] = $dt;
    }
}

$option_categories = ['ทั่วไป', 'สติ๊กเกอร์', 'ผ้าไวนิล', 'ตัวอักษรโลหะ', 'กล่องไฟ'];

if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'mat_deleted') $message .= "ลบวัสดุเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'opt_deleted') $message .= "ลบออปชันเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'rule_deleted') $message .= "ลบกฎราคาเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'updated') $message .= "อัปเดตข้อมูลเรียบร้อยแล้ว!<br>";
}

$stock_list = [];
$sql_stock = "SELECT stock_id, product_name, product_type, quantity, unit FROM stock ORDER BY product_type, product_name";
$result_stock = $conn->query($sql_stock);
if ($result_stock) {
    while ($row_stock = $result_stock->fetch_assoc()) {
        $stock_list[] = $row_stock;
    }
} else {
    $error .= "ไม่สามารถดึงข้อมูลสต็อกได้: " . $conn->error . "<br>";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการราคาสินค้าและสต็อก</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="icon" type="image/png" href="/icon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/icon/favicon.svg" />
    <link rel="shortcut icon" href="/icon/favicon.ico" />
</head>

<body>

    <nav class="navbar">
        <div class="navbar-container">
            <a href="admin.php" class="navbar-brand">จัดการข้อมูล</a>
            <div class="navbar-search-container">
                <svg class="icon-navbar" aria-hidden="true" viewBox="0 0 24 24">
                    <g>
                        <path
                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                        </path>
                    </g>
                </svg>
                <input type="text" id="globalSearch" class="search-box" placeholder="ค้นหาทั้งหมด...">
            </div>
            <a href="index.php" class="navbar-button">กลับไปหน้าคำนวณราคา</a>
        </div>
    </nav>

    <div class="container-body">
        <div id="globalMessages">
            <?php if (!empty($message)): ?>
            <div class="message success"><?php echo rtrim($message, "<br>"); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="message error"><?php echo rtrim($error, "<br>"); ?></div>
            <?php endif; ?>
        </div>
        <!-- Tabs Navigation -->
        <div class="admin-tabs">
            <button class="tab-btn active" onclick="switchTab('materials_section')">
                <svg class="tab-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                จัดการวัสดุ
            </button>
            <button class="tab-btn" onclick="switchTab('options_section')">
                <svg class="tab-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
                จัดการออปชัน
            </button>
            <button class="tab-btn" onclick="switchTab('rules_section')">
                <svg class="tab-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                จัดการกฎราคา
            </button>
            <button class="tab-btn stock-tab-btn" onclick="switchTab('stock_section')">
                <svg class="tab-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M20 13H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-6c0-.55-.45-1-1-1zM7 19c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM20 3H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1zM7 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>
                จัดการสต็อก
            </button>
        </div>

        <div class="admin-content-container">

                <div class="admin-section tab-content" id="rules_section">
                    <h2>จัดการกฎราคา</h2>
                    <div class="content-wrapper">
                        <div class="form-container">
                            <form action="admin.php#rules_section" method="post">
                                <div class="form-group">
                                    <label for="add_rule_name">ชื่อกฎราคา:</label>
                                    <input type="text" id="add_rule_name" name="add_rule_name" class="form-control "
                                        placeholder="ค่าบริการ" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_rule_value">ราคา:</label>
                                    <input type="number" step="0.01" id="add_rule_value" name="add_rule_value"
                                        class="form-control" placeholder="1,000" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_rule_unit">หน่วย:</label>
                                    <input type="text" id="add_rule_unit" name="add_rule_unit" class="form-control"
                                        placeholder="บาท" required>
                                </div>
                                <div class="btn-group">
                                    <button type="submit" name="add_rule">เพิ่ม</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-container">
                            <div class="search-section">
                                <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                    <g>
                                        <path
                                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                                        </path>
                                    </g>
                                </svg>
                                <input type="text" id="ruleSearch" class="search-box" placeholder="ค้นหากฎราคา...">
                            </div>
                            <div class="table-responsive">
                                <table id="rulesTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>ชื่อกฎ</th>
                                            <th>ค่า</th>
                                            <th>หน่วย</th>
                                            <th>จัดการ</th>
                                            <th>แสดงผล</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($price_rules_list)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลกฎราคา</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($price_rules_list as $rule): ?>
                                        <tr id="rule-row-<?php echo $rule['rule_id']; ?>">
                                            <td><?php echo htmlspecialchars($rule['rule_id']); ?></td>
                                            <td data-field="name"><?php echo htmlspecialchars($rule['rule_name']); ?>
                                            </td>
                                            <td data-field="value" style="text-align: right;">
                                                <?php echo number_format($rule['rule_value'], 2); ?></td>
                                            <td data-field="unit"><?php echo htmlspecialchars($rule['rule_unit']); ?>
                                            </td>
                                            <td class="table-actions">
                                                <button type="button" class="btn-edit"
                                                    onclick="openEditModal('rule', <?php echo $rule['rule_id']; ?>)">
                                                    <svg class="icon-edit" viewBox="0 0 512 512" width="16" height="16">
                                                        <path
                                                            d="M500.633,211.454l-58.729-14.443c-3.53-11.133-8.071-21.929-13.55-32.256c8.818-14.678,27.349-45.571,27.349-45.571c3.545-5.903,2.607-13.462-2.256-18.325l-42.422-42.422c-4.863-4.878-12.407-5.815-18.325-2.256L347.055,83.53c-10.269-5.435-21.006-9.932-32.065-13.433l-14.443-58.729C298.876,4.688,292.885,0,286,0h-60c-6.885,0-12.891,4.688-14.546,11.367c0,0-10.005,40.99-14.429,58.715c-11.792,3.735-23.188,8.584-34.043,14.502l-47.329-28.403c-5.918-3.516-13.447-2.607-18.325,2.256l-42.422,42.422c-4.863,4.863-5.801,12.422-2.256,18.325l29.268,48.882c-4.717,9.302-8.672,18.984-11.821,28.901l-58.729,14.487C4.688,213.124,0,219.115,0,226v60c0,6.885,4.688,12.891,11.367,14.546l58.744,14.443c3.56,11.294,8.188,22.266,13.799,32.798l-26.191,43.652c-3.545,5.903-2.607,13.462,2.256,18.325l42.422,42.422c4.849,4.849,12.407,5.771,18.325,2.256c0,0,29.37-17.607,43.755-26.221c10.415,5.552,21.313,10.137,32.549,13.696l14.429,58.715C213.109,507.313,219.115,512,226,512h60c6.885,0,12.876-4.688,14.546-11.367l14.429-58.715c11.558-3.662,22.69-8.394,33.281-14.136c14.78,8.862,44.443,26.66,44.443,26.66c5.903,3.53,13.462,2.622,18.325-2.256l42.422-42.422c4.863-4.863,5.801-12.422,2.256-18.325l-26.968-44.927c5.317-10.093,9.727-20.654,13.169-31.523l58.729-14.443C507.313,298.876,512,292.885,512,286v-60C512,219.115,507.313,213.124,500.633,211.454z M256,361c-57.891,0-105-47.109-105-105s47.109-105,105-105s105,47.109,105,105S313.891,361,256,361z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('rule', <?php echo $rule['rule_id']; ?>, '<?php echo htmlspecialchars(addslashes($rule['rule_name'])); ?>')"><svg
                                                        viewBox="0 0 16 16" fill="currentColor">
                                                        <path
                                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z" />
                                                        <path fill-rule="evenodd"
                                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                                    </svg></button>
                                            </td>
                                            <td>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" class="original-checkbox rule-display-toggle"
                                                        data-rule-id="<?php echo $rule['rule_id']; ?>"
                                                        <?php echo (isset($rule['display_in_calculator']) && $rule['display_in_calculator'] == 1) ? 'checked' : ''; ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-section tab-content" id="materials_section">
                    <h2>จัดการวัสดุ</h2>
                    <div class="content-wrapper">
                        <div class="form-container">
                            <form action="admin.php#materials_section" method="post">
                                <div class="form-group">
                                    <label for="add_material_type">ประเภทวัสดุ:</label>
                                    <select id="add_material_type" name="add_material_type" class="form-control"
                                        required>
                                        <?php foreach (array_unique($existing_product_types) as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                            <?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="add_material_name">ชื่อวัสดุ:</label>
                                    <input type="text" id="add_material_name" name="add_material_name"
                                        class="form-control" placeholder="สแตนเลส" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_material_price">ราคาต่อหน่วย:</label>
                                    <input type="number" step="0.01" id="add_material_price" name="add_material_price"
                                        class="form-control" placeholder="80 บาท" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_material_unit">หน่วย:</label>
                                    <input type="text" id="add_material_unit" name="add_material_unit"
                                        class="form-control" placeholder="บาท/นิ้ว" required>
                                </div>
                                <div class="btn-group">
                                    <button type="submit" name="add_material">เพิ่ม</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-container">
                            <div class="search-section">
                                <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                    <g>
                                        <path
                                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                                        </path>
                                    </g>
                                </svg>
                                <input type="text" id="materialSearch" class="search-box"
                                    placeholder="ค้นหาด้วยชื่อ หรือ ประเภท...">
                            </div>
                            <div class="table-responsive">
                                <table id="materialsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>ประเภท</th>
                                            <th>ชื่อวัสดุ</th>
                                            <th>ราคา/หน่วย</th>
                                            <th>หน่วย</th>
                                            <th>จัดการ</th>
                                            <th>แสดงผล</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($materials_list_admin)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center;">ยังไม่มีข้อมูลวัสดุ</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($materials_list_admin as $mat): ?>
                                        <tr id="material-row-<?php echo $mat['material_id']; ?>">
                                            <td><?php echo htmlspecialchars($mat['material_id']); ?></td>
                                            <td data-field="type"><?php echo htmlspecialchars($mat['product_type']); ?>
                                            </td>
                                            <td data-field="name"><?php echo htmlspecialchars($mat['material_name']); ?>
                                            </td>
                                            <td data-field="price" style="text-align:right;">
                                                <?php echo number_format($mat['price_per_unit'], 2); ?></td>
                                            <td data-field="unit"><?php echo htmlspecialchars($mat['unit']); ?></td>
                                            <td class="table-actions">
                                                <button type="button" class="btn-edit"
                                                    onclick="openEditModal('material', <?php echo $mat['material_id']; ?>)">
                                                    <svg class="icon-edit" viewBox="0 0 512 512" width="16" height="16">
                                                        <path
                                                            d="M500.633,211.454l-58.729-14.443c-3.53-11.133-8.071-21.929-13.55-32.256c8.818-14.678,27.349-45.571,27.349-45.571c3.545-5.903,2.607-13.462-2.256-18.325l-42.422-42.422c-4.863-4.878-12.407-5.815-18.325-2.256L347.055,83.53c-10.269-5.435-21.006-9.932-32.065-13.433l-14.443-58.729C298.876,4.688,292.885,0,286,0h-60c-6.885,0-12.891,4.688-14.546,11.367c0,0-10.005,40.99-14.429,58.715c-11.792,3.735-23.188,8.584-34.043,14.502l-47.329-28.403c-5.918-3.516-13.447-2.607-18.325,2.256l-42.422,42.422c-4.863,4.863-5.801,12.422-2.256,18.325l29.268,48.882c-4.717,9.302-8.672,18.984-11.821,28.901l-58.729,14.487C4.688,213.124,0,219.115,0,226v60c0,6.885,4.688,12.891,11.367,14.546l58.744,14.443c3.56,11.294,8.188,22.266,13.799,32.798l-26.191,43.652c-3.545,5.903-2.607,13.462,2.256,18.325l42.422,42.422c4.849,4.849,12.407,5.771,18.325,2.256c0,0,29.37-17.607,43.755-26.221c10.415,5.552,21.313,10.137,32.549,13.696l14.429,58.715C213.109,507.313,219.115,512,226,512h60c6.885,0,12.876-4.688,14.546-11.367l14.429-58.715c11.558-3.662,22.69-8.394,33.281-14.136c14.78,8.862,44.443,26.66,44.443,26.66c5.903,3.53,13.462,2.622,18.325-2.256l42.422-42.422c4.863-4.863,5.801-12.422,2.256-18.325l-26.968-44.927c5.317-10.093,9.727-20.654,13.169-31.523l58.729-14.443C507.313,298.876,512,292.885,512,286v-60C512,219.115,507.313,213.124,500.633,211.454z M256,361c-57.891,0-105-47.109-105-105s47.109-105,105-105s105,47.109,105,105S313.891,361,256,361z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('material', <?php echo $mat['material_id']; ?>, '<?php echo htmlspecialchars(addslashes($mat['material_name'])); ?>')"><svg
                                                        viewBox="0 0 16 16" fill="currentColor">
                                                        <path
                                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z" />
                                                        <path fill-rule="evenodd"
                                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                                    </svg></button>
                                            </td>
                                            <td>
                                                <label class="toggle-switch">
                                                    <input type="checkbox"
                                                        class="original-checkbox material-display-toggle"
                                                        data-material-id="<?php echo $mat['material_id']; ?>"
                                                        <?php echo (isset($mat['display_in_calculator']) && $mat['display_in_calculator'] == 1) ? 'checked' : ''; ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-section tab-content" id="options_section">
                    <h2>จัดการออปชันเสริม</h2>
                    <div class="content-wrapper">
                        <div class="form-container">
                            <form action="admin.php#options_section" method="post">
                                <div class="form-group">
                                    <label for="add_option_name">ชื่อออปชัน:</label>
                                    <input type="text" id="add_option_name" name="add_option_name" class="form-control"
                                        placeholder="เสาเหล็ก" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_option_price">ราคา (บาท):</label>
                                    <input type="number" step="0.01" id="add_option_price" name="add_option_price"
                                        class="form-control" placeholder="1,000 บาท" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_option_category">หมวดหมู่:</label>
                                    <select id="add_option_category" name="add_option_category" class="form-control"
                                        required>
                                        <?php foreach ($option_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button type="submit" name="add_option">เพิ่ม</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-container">
                            <div class="search-section">
                                <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                    <g>
                                        <path
                                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                                        </path>
                                    </g>
                                </svg>
                                <input type="text" id="optionSearch" class="search-box"
                                    placeholder="ค้นหาด้วยชื่อหรือหมวดหมู่...">
                            </div>
                            <div class="table-responsive">
                                <table id="optionsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>ชื่อออปชัน</th>
                                            <th>ราคา</th>
                                            <th>หมวดหมู่</th>
                                            <th>จัดการ</th>
                                            <th>แสดงผล</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($options_list_admin)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลออปชัน</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($options_list_admin as $opt): ?>
                                        <tr id="option-row-<?php echo $opt['option_id']; ?>">
                                            <td><?php echo htmlspecialchars($opt['option_id']); ?></td>
                                            <td data-field="name"><?php echo htmlspecialchars($opt['option_name']); ?>
                                            </td>
                                            <td data-field="price" style="text-align:right;">
                                                <?php echo number_format($opt['option_price'], 2); ?></td>
                                            <td data-field="category">
                                                <?php echo htmlspecialchars($opt['category'] ?? 'ทั่วไป'); ?></td>
                                            <td class="table-actions">
                                                <button type="button" class="btn-edit"
                                                    onclick="openEditModal('option', <?php echo $opt['option_id']; ?>)">
                                                    <svg class="icon-edit" viewBox="0 0 512 512" width="16" height="16">
                                                        <path
                                                            d="M500.633,211.454l-58.729-14.443c-3.53-11.133-8.071-21.929-13.55-32.256c8.818-14.678,27.349-45.571,27.349-45.571c3.545-5.903,2.607-13.462-2.256-18.325l-42.422-42.422c-4.863-4.878-12.407-5.815-18.325-2.256L347.055,83.53c-10.269-5.435-21.006-9.932-32.065-13.433l-14.443-58.729C298.876,4.688,292.885,0,286,0h-60c-6.885,0-12.891,4.688-14.546,11.367c0,0-10.005,40.99-14.429,58.715c-11.792,3.735-23.188,8.584-34.043,14.502l-47.329-28.403c-5.918-3.516-13.447-2.607-18.325,2.256l-42.422,42.422c-4.863,4.863-5.801,12.422-2.256,18.325l29.268,48.882c-4.717,9.302-8.672,18.984-11.821,28.901l-58.729,14.487C4.688,213.124,0,219.115,0,226v60c0,6.885,4.688,12.891,11.367,14.546l58.744,14.443c3.56,11.294,8.188,22.266,13.799,32.798l-26.191,43.652c-3.545,5.903-2.607,13.462,2.256,18.325l42.422,42.422c4.849,4.849,12.407,5.771,18.325,2.256c0,0,29.37-17.607,43.755-26.221c10.415,5.552,21.313,10.137,32.549,13.696l14.429,58.715C213.109,507.313,219.115,512,226,512h60c6.885,0,12.876-4.688,14.546-11.367l14.429-58.715c11.558-3.662,22.69-8.394,33.281-14.136c14.78,8.862,44.443,26.66,44.443,26.66c5.903,3.53,13.462,2.622,18.325-2.256l42.422-42.422c4.863-4.863,5.801-12.422,2.256-18.325l-26.968-44.927c5.317-10.093,9.727-20.654,13.169-31.523l58.729-14.443C507.313,298.876,512,292.885,512,286v-60C512,219.115,507.313,213.124,500.633,211.454z M256,361c-57.891,0-105-47.109-105-105s47.109-105,105-105s105,47.109,105,105S313.891,361,256,361z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('option', <?php echo $opt['option_id']; ?>, '<?php echo htmlspecialchars(addslashes($opt['option_name'])); ?>')"><svg
                                                        viewBox="0 0 16 16" fill="currentColor">
                                                        <path
                                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z" />
                                                        <path fill-rule="evenodd"
                                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                                    </svg></button>
                                            </td>
                                            <td>
                                                <label class="toggle-switch">
                                                    <input type="checkbox"
                                                        class="original-checkbox option-display-toggle"
                                                        data-option-id="<?php echo $opt['option_id']; ?>"
                                                        <?php echo (isset($opt['display_in_calculator']) && $opt['display_in_calculator'] == 1) ? 'checked' : ''; ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="admin-section tab-content" id="stock_section">
                <h2>จัดการสต็อกสินค้า</h2>
                <div class="content-wrapper">
                    <div class="form-container">
                        <form action="admin.php#stock_section" method="post">
                            <div class="form-group">
                                <label for="add_stock_product_name">ชื่อสินค้า:</label>
                                <input type="text" id="add_stock_product_name" name="add_stock_product_name"
                                    class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="add_stock_product_type">ประเภทสินค้า:</label>
                                <input type="text" id="add_stock_product_type" name="add_stock_product_type"
                                    class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="add_stock_quantity">จำนวน:</label>
                                <input type="number" step="1" id="add_stock_quantity" name="add_stock_quantity"
                                    class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="add_stock_unit">หน่วย:</label>
                                <input type="text" id="add_stock_unit" name="add_stock_unit" class="form-control" required>
                            </div>
                            <div class="btn-group">
                                <button type="submit" name="add_stock_item">เพิ่มสินค้าในสต็อก</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-container">
                        <div class="filter-section">
                            <div class="search-section">
                                <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                    <g>
                                        <path
                                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                                        </path>
                                    </g>
                                </svg>
                                <input type="text" id="stockSearch" class="search-box"
                                    placeholder="ค้นหาชื่อสินค้า, หน่วย...">
                            </div>
                            <div>
                                <label for="stockTypeFilter">หมวดหมู่:</label>
                                <select id="stockTypeFilter" class="form-control">
                                    <option value="">ทั้งหมด</option>
                                    <?php
                                    // CHANGED/ADDED: ดึง Product Types ที่มีอยู่จาก $stock_list
                                    $product_types_in_stock = [];
                                    if (!empty($stock_list)) {
                                        foreach ($stock_list as $item) {
                                            $product_types_in_stock[] = $item['product_type'];
                                        }
                                    }
                                    $unique_product_types = array_unique($product_types_in_stock);
                                    sort($unique_product_types); // เรียงตามตัวอักษร (ถ้าต้องการ)
                                    foreach ($unique_product_types as $type):
                                    ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="stockManagementTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ประเภท</th>
                                        <th>ชื่อสินค้า</th>
                                        <th>จำนวน</th>
                                        <th>หน่วย</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($stock_list)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลสต็อก</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($stock_list as $item): ?>
                                    <tr id="stock-row-<?php echo $item['stock_id']; ?>">
                                        <td><?php echo htmlspecialchars($item['stock_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td>
                                            <input type="number" class="stock-quantity-input form-control"
                                                style="width: 80px; text-align: right;"
                                                value="<?php echo $item['quantity']; ?>"
                                                data-initial-value="<?php echo $item['quantity']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td class="table-actions">
                                            <button type="button" class="btn-stock-save"
                                                onclick="handleStockUpdate(<?php echo $item['stock_id']; ?>, this)">✓</button>
                                            <button type="button" class="btn-delete"
                                                onclick="handleDeleteClick('stock', <?php echo $item['stock_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['product_name'])); ?>')"><svg
                                                    viewBox="0 0 16 16" fill="currentColor">
                                                    <path
                                                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z" />
                                                    <path fill-rule="evenodd"
                                                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                                </svg></button>
                                        <button type="button" class="btn-edit"
                                            onclick="openEditModal('stock', <?php echo $item['stock_id']; ?>)">
                                            <svg class="icon-edit" viewBox="0 0 512 512" width="16" height="16">
                                                <path
                                                    d="M500.633,211.454l-58.729-14.443c-3.53-11.133-8.071-21.929-13.55-32.256c8.818-14.678,27.349-45.571,27.349-45.571c3.545-5.903,2.607-13.462-2.256-18.325l-42.422-42.422c-4.863-4.878-12.407-5.815-18.325-2.256L347.055,83.53c-10.269-5.435-21.006-9.932-32.065-13.433l-14.443-58.729C298.876,4.688,292.885,0,286,0h-60c-6.885,0-12.891,4.688-14.546,11.367c0,0-10.005,40.99-14.429,58.715c-11.792,3.735-23.188,8.584-34.043,14.502l-47.329-28.403c-5.918-3.516-13.447-2.607-18.325,2.256l-42.422,42.422c-4.863,4.863-5.801,12.422-2.256,18.325l29.268,48.882c-4.717,9.302-8.672,18.984-11.821,28.901l-58.729,14.487C4.688,213.124,0,219.115,0,226v60c0,6.885,4.688,12.891,11.367,14.546l58.744,14.443c3.56,11.294,8.188,22.266,13.799,32.798l-26.191,43.652c-3.545,5.903-2.607,13.462,2.256,18.325l42.422,42.422c4.849,4.849,12.407,5.771,18.325,2.256c0,0,29.37-17.607,43.755-26.221c10.415,5.552,21.313,10.137,32.549,13.696l14.429,58.715C213.109,507.313,219.115,512,226,512h60c6.885,0,12.876-4.688,14.546-11.367l14.429-58.715c11.558-3.662,22.69-8.394,33.281-14.136c14.78,8.862,44.443,26.66,44.443,26.66c5.903,3.53,13.462,2.622,18.325-2.256l42.422-42.422c4.863-4.863,5.801-12.422,2.256-18.325l-26.968-44.927c5.317-10.093,9.727-20.654,13.169-31.523l58.729-14.443C507.313,298.876,512,292.885,512,286v-60C512,219.115,507.313,213.124,500.633,211.454z M256,361c-57.891,0-105-47.109-105-105s47.109-105,105-105s105,47.109,105,105S313.891,361,256,361z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <?php include 'includes/modals.php'; ?>
    </div>
    <script src="js/admin.js" defer></script>

<script>
// --- Tab Switching Logic ---
function switchTab(tabId) {
    // 1. Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    
    // 2. Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // 3. Show selected tab content
    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = 'block';
        // Trigger resize if needed (for charts or grids)
        window.dispatchEvent(new Event('resize'));
    }
    
    // 4. Add active class to clicked button
    // Find button with matching onclick or data-tab
    const btn = document.querySelector(`button[onclick="switchTab('${tabId}')"]`);
    if (btn) btn.classList.add('active');

    // 5. Update URL hash without scrolling
    history.replaceState(null, null, '#' + tabId);
    
    // 6. Save to localStorage
    localStorage.setItem('activeAdminTab', tabId);
}

// --- Initialize Tab on Load ---
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    const saved = localStorage.getItem('activeAdminTab');
    const defaultTab = 'materials_section'; // Default tab
    
    let tabToOpen = defaultTab;
    if (hash && document.getElementById(hash)) {
        tabToOpen = hash;
    } else if (saved && document.getElementById(saved)) {
        tabToOpen = saved;
    }
    
    switchTab(tabToOpen);
});

// --- Global fallback to ensure openEditModal exists (in case js/admin.js didn't run) ---
if (typeof window.openEditModal !== 'function') {
  window.openEditModal = function(type, id){
    try{
      var modalId = 'edit' + type.charAt(0).toUpperCase() + type.slice(1) + 'Modal';
      var formId  = 'edit' + type.charAt(0).toUpperCase() + type.slice(1) + 'Form';
      var url     = 'admin_ajax_data_handler.php?action=get_' + type + '_data&id=' + encodeURIComponent(id);
      var modal = document.getElementById(modalId);
      var form  = document.getElementById(formId);
      if (!modal || !form) { alert('ไม่พบโมดอลหรือฟอร์ม: ' + modalId); return; }
      fetch(url).then(r => r.json()).then(function(data){
        if (!data || !data.success || !data.data) throw new Error(data && data.error ? data.error : 'โหลดข้อมูลไม่สำเร็จ');
        var item = data.data;
        form.reset();
        if (form.elements[type+'_id']) form.elements[type+'_id'].value = id;
        Object.keys(item).forEach(function(k){
          if (form.elements[k]) form.elements[k].value = item[k];
        });
        if (type === 'material') {
          if (form.elements['material_price'] && item['price_per_unit'] != null) form.elements['material_price'].value = item['price_per_unit'];
          if (form.elements['material_type'] && item['product_type']) form.elements['material_type'].value = item['product_type'];
        }
        if (type === 'option') {
          if (form.elements['option_category'] && item['category']) form.elements['option_category'].value = item['category'];
        }
        modal.style.display = 'flex';
        var first = form.querySelector('input,select,textarea'); if (first) first.focus();
      }).catch(function(err){
        alert('โหลดข้อมูลไม่สำเร็จ: ' + err.message);
      });
    }catch(e){ alert('เกิดข้อผิดพลาด: ' + e.message); }
  };
}
// also ensure handleDeleteClick exists
if (typeof window.handleDeleteClick !== 'function') {
  window.handleDeleteClick = function(type, id, name) {
    if (!confirm('ต้องการลบ "' + (name||'รายการ') + '" ใช่หรือไม่?')) return;
    var key = {rule:'delete_rule_id', material:'delete_material_id', option:'delete_option_id', stock:'delete_stock_id'}[type] || 'delete_id';
    var url = new URL(window.location.href); url.searchParams.set(key, id); window.location.href = url.toString();
  };
}
</script>
</body>

</html>