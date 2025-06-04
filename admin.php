<?php
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include 'includes/db_connect.php';

$message = '';
$error = '';

// ส่วนจัดการการกระทำ (Actions) - ลบผ่าน GET (ควรจะเลิกใช้ถ้า AJAX delete ทำงานสมบูรณ์)
if (isset($_GET['action']) && isset($_GET['id']) && strpos($_GET['action'], 'delete_') === 0) {
    // ... (โค้ดส่วนลบที่มีอยู่เหมือนเดิม) ...
    $delete_id = intval($_GET['id']);
    $item_type = '';
    $table_name = '';
    $id_column = '';
    $redirect_msg = '';
    $section_anchor = '';
    if ($_GET['action'] == 'delete_rule') {
        $item_type = 'กฎราคา';
        $table_name = 'price_rules';
        $id_column = 'rule_id';
        $redirect_msg = 'rule_deleted';
        $section_anchor = '#rules_section';
    } elseif ($_GET['action'] == 'delete_option') {
        $item_type = 'ออปชัน';
        $table_name = 'options';
        $id_column = 'option_id';
        $redirect_msg = 'opt_deleted';
        $section_anchor = '#options_section';
    } elseif ($_GET['action'] == 'delete_material') {
        $item_type = 'วัสดุ';
        $table_name = 'materials';
        $id_column = 'material_id';
        $redirect_msg = 'mat_deleted';
        $section_anchor = '#materials_section';
    } elseif ($_GET['action'] == 'delete_stock') {
        $item_type = 'สินค้าในสต็อก';
        $table_name = 'stock';
        $id_column = 'stock_id';
        $redirect_msg = 'stock_deleted';
        $section_anchor = '#stock_section';
    }

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
        <div class="main-layout-grid">

            <div class="main-admin-content">
                <div class="admin-section" id="rules_section">
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
                                        class="form-control" placeholder="1,000"required>
                                </div>
                                <div class="form-group">
                                    <label for="add_rule_unit">หน่วย:</label>
                                    <input type="text" id="add_rule_unit" name="add_rule_unit" class="form-control" placeholder="บาท"
                                        required>
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
                                                    onclick="openEditModal('rule', <?php echo $rule['rule_id']; ?>)">แก้ไข</button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('rule', <?php echo $rule['rule_id']; ?>, '<?php echo htmlspecialchars(addslashes($rule['rule_name'])); ?>')">ลบ</button>
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
                <div class="admin-section" id="materials_section">
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
                                        class="form-control" placeholder="บาท/นิ้ว"required>
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
                                                    onclick="openEditModal('material', <?php echo $mat['material_id']; ?>)">แก้ไข</button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('material', <?php echo $mat['material_id']; ?>, '<?php echo htmlspecialchars(addslashes($mat['material_name'])); ?>')">ลบ</button>
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
                <div class="admin-section" id="options_section">
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
                                                    onclick="openEditModal('option', <?php echo $opt['option_id']; ?>)">แก้ไข</button>
                                                <button type="button" class="btn-delete"
                                                    onclick="handleDeleteClick('option', <?php echo $opt['option_id']; ?>, '<?php echo htmlspecialchars(addslashes($opt['option_name'])); ?>')">ลบ</button>
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
            </div>
            <div class="admin-section" id="stock_section">
                <h2>จัดการสต็อกสินค้า</h2>

                <div class="form-container" style="margin-bottom: 20px;">
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
                    <div class="filter-section" style="display: flex; gap: 15px; margin-bottom: 15px; align-items: center;">
                        <div class="search-section" style="margin-bottom: 0;">
                            <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                                <g><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"></path></g>
                            </svg>
                            <input type="text" id="stockSearch" class="search-box" placeholder="ค้นหาชื่อสินค้า, หน่วย...">
                        </div>
                        <div>
                            <label for="stockTypeFilter" style="margin-right: 5px; font-size: 0.9rem; color: #495057;">หมวดหมู่:</label>
                            <select id="stockTypeFilter" class="form-control" style="width: auto; min-width: 180px; display: inline-block; padding: 8px 10px; height: 40px; line-height: 1.5;">
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
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
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
                                            onclick="handleDeleteClick('stock', <?php echo $item['stock_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['product_name'])); ?>')">ลบ</button>
                                        <button type="button" class="btn-edit"
                                            onclick="openEditModal('stock', <?php echo $item['stock_id']; ?>)">แก้ไข</button>
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
</body>

</html>