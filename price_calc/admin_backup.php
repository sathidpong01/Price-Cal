<?php
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include 'includes/db_connect.php'; //

$message = ''; // สำหรับแสดงข้อความแจ้งเตือน
$error = '';   // สำหรับแสดงข้อผิดพลาด
// $edit_rule_data, $edit_material_data, $edit_option_data ถูกลบออกไปแล้ว

// ------------------------------------------------------------------
// ส่วนจัดการการกระทำ (Actions) - ลบ (ต้องทำก่อนดึงข้อมูล)
// ------------------------------------------------------------------
if (isset($_GET['action']) && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    $item_type = ''; $table_name = ''; $id_column = ''; $redirect_msg = ''; $section_anchor = '';
    if ($_GET['action'] == 'delete_rule') { $item_type = 'กฎราคา'; $table_name = 'price_rules'; $id_column = 'rule_id'; $redirect_msg = 'rule_deleted'; $section_anchor = '#rules_section'; }
    elseif ($_GET['action'] == 'delete_option') { $item_type = 'ออปชัน'; $table_name = 'options'; $id_column = 'option_id'; $redirect_msg = 'opt_deleted'; $section_anchor = '#options_section'; }
    elseif ($_GET['action'] == 'delete_material') { $item_type = 'วัสดุ'; $table_name = 'materials'; $id_column = 'material_id'; $redirect_msg = 'mat_deleted'; $section_anchor = '#materials_section'; }
    if (!empty($table_name)) {
        $sql_delete = "DELETE FROM {$table_name} WHERE {$id_column} = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        if ($stmt_delete) { $stmt_delete->bind_param("i", $delete_id); if (!$stmt_delete->execute()) { $error .= "เกิดข้อผิดพลาดในการลบ{$item_type}: " . $stmt_delete->error . "<br>"; } $stmt_delete->close(); if(empty($error)) { header("Location: admin.php?msg={$redirect_msg}{$section_anchor}"); exit; }
        } else { $error .= "เกิดข้อผิดพลาด SQL ลบ{$item_type}: " . $conn->error . "<br>"; }
    }
}
// ------------------------------------------------------------------
// ส่วนจัดการการกระทำ (Actions) - เพิ่ม / แก้ไขค่าในตาราง (รับข้อมูล POST จากฟอร์มหลัก)
// ------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- จัดการการเพิ่มกฎราคา ---
    if (isset($_POST['add_rule'])) {
        $rule_name = $_POST['add_rule_name']; $rule_value = $_POST['add_rule_value']; $rule_unit = $_POST['add_rule_unit'];
        if (!empty($rule_name) && is_numeric($rule_value) && $rule_value >= 0 && !empty($rule_unit)) {
            $sql_add = "INSERT INTO price_rules (rule_name, rule_value, rule_unit) VALUES (?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) { $stmt_add->bind_param("sds", $rule_name, $rule_value, $rule_unit); if ($stmt_add->execute()) { $message .= "เพิ่มกฎราคา '" . htmlspecialchars($rule_name) . "' เรียบร้อย!<br>"; } else { $error .= "ผิดพลาด เพิ่มกฎราคา: " . $stmt_add->error . "<br>"; } $stmt_add->close();
            } else { $error .= "ผิดพลาด SQL เพิ่มกฎราคา<br>"; }
        } else { $error .= "กรุณากรอกข้อมูลกฎราคาใหม่ให้ครบ!<br>"; }
    }

    // --- จัดการการเพิ่มออปชัน ---
    if (isset($_POST['add_option'])) {
        $opt_name = $_POST['add_option_name']; $opt_price = $_POST['add_option_price'];
        if (!empty($opt_name) && is_numeric($opt_price) && $opt_price >= 0) {
            $sql_add = "INSERT INTO options (option_name, option_price) VALUES (?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) { $stmt_add->bind_param("sd", $opt_name, $opt_price); if ($stmt_add->execute()) { $message .= "เพิ่มออปชัน '" . htmlspecialchars($opt_name) . "' เรียบร้อย!<br>"; } else { $error .= "ผิดพลาด เพิ่มออปชัน: " . $stmt_add->error . "<br>"; } $stmt_add->close();
            } else { $error .= "ผิดพลาด SQL เพิ่มออปชัน<br>"; }
        } else { $error .= "กรุณากรอกข้อมูลออปชันใหม่ให้ครบ!<br>"; }
    }
    // --- จัดการการเพิ่มวัสดุ ---
    if (isset($_POST['add_material'])) {
        $mat_type = $_POST['add_material_type']; $mat_name = $_POST['add_material_name']; $mat_price = $_POST['add_material_price']; $mat_unit = $_POST['add_material_unit'];
        if (!empty($mat_type) && !empty($mat_name) && is_numeric($mat_price) && $mat_price >= 0 && !empty($mat_unit)) {
            $sql_add = "INSERT INTO materials (product_type, material_name, price_per_unit, unit) VALUES (?, ?, ?, ?)";
            $stmt_add = $conn->prepare($sql_add);
            if ($stmt_add) { $stmt_add->bind_param("ssds", $mat_type, $mat_name, $mat_price, $mat_unit); if ($stmt_add->execute()) { $message .= "เพิ่มวัสดุ '" . htmlspecialchars($mat_name) . "' เรียบร้อย!<br>"; } else { $error .= "ผิดพลาด เพิ่มวัสดุ: " . $stmt_add->error . "<br>"; } $stmt_add->close();
            } else { $error .= "ผิดพลาด SQL เพิ่มวัสดุ<br>"; }
        } else { $error .= "กรุณากรอกข้อมูลวัสดุใหม่ให้ครบ!<br>"; }
    }
    // การแก้ไขข้อมูล (update_rule, update_material, update_option) จะถูกส่งไปที่ admin_ajax_data_handler.php
}
// ------------------------------------------------------------------
// ส่วนดึงข้อมูล (Fetch Data) สำหรับแสดงผลในตาราง
// ------------------------------------------------------------------
$price_rules_list = [];
$sql_select_rules = "SELECT rule_id, rule_name, rule_value, rule_unit FROM price_rules ORDER BY rule_id";
$result_rules = $conn->query($sql_select_rules);
if ($result_rules) { while ($row_rule = $result_rules->fetch_assoc()) { $price_rules_list[] = $row_rule; } } else { $error .= "ไม่สามารถดึงข้อมูลกฎราคาได้<br>"; }

$materials_list_admin = [];
$sql_select_mats = "SELECT material_id, product_type, material_name, price_per_unit, unit FROM materials ORDER BY product_type, material_name";
$result_mats = $conn->query($sql_select_mats);
if ($result_mats) { while ($row_mat = $result_mats->fetch_assoc()) { $materials_list_admin[] = $row_mat; } } else { $error .= "ไม่สามารถดึงข้อมูลวัสดุได้<br>"; }

$options_list_admin = [];
$sql_select_opts = "SELECT option_id, option_name, option_price FROM options ORDER BY option_name";
$result_opts = $conn->query($sql_select_opts);
if ($result_opts) { while ($row_opt = $result_opts->fetch_assoc()) { $options_list_admin[] = $row_opt; } } else { $error .= "ไม่สามารถดึงข้อมูลออปชันได้<br>"; }

$existing_product_types = [];
$sql_types = "SELECT DISTINCT product_type FROM materials";
$result_types = $conn->query($sql_types);
if($result_types){ while($row_type = $result_types->fetch_assoc()){ $existing_product_types[] = $row_type['product_type']; } }
if(!in_array('ตัวอักษรโลหะ', $existing_product_types)) $existing_product_types[] = 'ตัวอักษรโลหะ';
if(!in_array('กล่องไฟ', $existing_product_types)) $existing_product_types[] = 'กล่องไฟ';
if(!in_array('วัสดุแผ่น', $existing_product_types)) $existing_product_types[] = 'วัสดุแผ่น';

if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'mat_deleted') $message .= "ลบวัสดุเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'opt_deleted') $message .= "ลบออปชันเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'rule_deleted') $message .= "ลบกฎราคาเรียบร้อยแล้ว!<br>";
    if($_GET['msg'] == 'updated') $message .= "อัปเดตข้อมูลเรียบร้อยแล้ว!<br>";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการราคาสินค้า</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <div class="container">
        <div style="text-align: right; margin-bottom: 15px;"><a href="index.php" class="btn btn-secondary"
                style="background-color: #6c757d;">กลับไปหน้าคำนวณราคา</a></div>
        <h1>จัดการราคาสินค้า</h1>
        <div id="globalMessages"> <?php if (!empty($message)): ?><div class="message success">
                <?php echo rtrim($message, "<br>"); ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="message error"><?php echo rtrim($error, "<br>"); ?></div>
            <?php endif; ?>
        </div>

        <div class="admin-section" id="rules_section">
            <h2>จัดการกฎราคา (Price Rules)</h2>
            <form action="admin.php#rules_section" method="post">
                <h3>เพิ่มกฎราคาใหม่</h3>
                <div class="form-group"><label for="add_rule_name">ชื่อกฎราคา:</label><input type="text"
                        id="add_rule_name" name="add_rule_name" class="form-control" required></div>
                <div class="form-group"><label for="add_rule_value">ราคา(บาท):</label><input type="number" step="0.01"
                        id="add_rule_value" name="add_rule_value" class="form-control" required></div>
                <div class="form-group"><label for="add_rule_unit">หน่วย:</label><input type="text" id="add_rule_unit"
                        name="add_rule_unit" class="form-control" required></div>
                <div class="btn-group"><button type="submit" name="add_rule">เพิ่มกฎราคา</button></div>
            </form>

            <h3>รายการกฎราคาทั้งหมด</h3>
            <div class="search-section"><input type="text" id="ruleSearch" class="search-box"
                    placeholder="ค้นหากฎราคา..." onkeyup="filterTable('ruleSearch', 'rulesTable', 1)"></div>
            <div class="table-responsive">
                <form action="admin.php#rules_section" method="post">
                    <table id="rulesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อกฎ</th>
                                <th>ราคา(บาท)</th>
                                <th>หน่วย</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($price_rules_list)): ?><tr>
                                <td colspan="5" style="text-align: center;">ยังไม่มีข้อมูลกฎราคา</td>
                            </tr>
                            <?php else: ?><?php foreach ($price_rules_list as $rule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rule['rule_id']); ?></td>
                                <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                                <td style="text-align: right;"><?php echo number_format($rule['rule_value'], 2); ?></td>
                                <td><?php echo htmlspecialchars($rule['rule_unit']); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-edit"
                                        onclick="openEditModal('rule', <?php echo $rule['rule_id']; ?>)">แก้ไข</button>
                                    <a href="admin.php?action=delete_rule&id=<?php echo $rule['rule_id']; ?>"
                                        class="btn btn-delete"
                                        onclick="return confirmDelete('กฎราคา', '<?php echo htmlspecialchars(addslashes($rule['rule_name'])); ?>')">ลบ</a>
                                </td>
                            </tr>
                            <?php endforeach; ?><?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

        <div class="admin-section" id="materials_section">
            <h2>จัดการวัสดุ (Materials)</h2>
            <form action="admin.php#materials_section" method="post">
                <h3>เพิ่มวัสดุใหม่</h3>
                <div class="form-group"><label for="add_material_type">ประเภทวัสดุ:</label><select
                        id="add_material_type" name="add_material_type" class="form-control"
                        required><?php foreach ($existing_product_types as $type): ?><option
                            value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?>
                        </option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="add_material_name">ชื่อวัสดุ:</label><input type="text"
                        id="add_material_name" name="add_material_name" class="form-control" required></div>
                <div class="form-group"><label for="add_material_price">ราคาต่อหน่วย:</label><input type="number"
                        step="0.01" id="add_material_price" name="add_material_price" class="form-control" required>
                </div>
                <div class="form-group"><label for="add_material_unit">หน่วย:</label><input type="text"
                        id="add_material_unit" name="add_material_unit" class="form-control" required></div>
                <div class="btn-group"><button type="submit" name="add_material">เพิ่มวัสดุ</button></div>
            </form>
            <h3>รายการวัสดุทั้งหมด</h3>
            <div class="search-section"><input type="text" id="materialSearch" class="search-box"
                    placeholder="ค้นหาด้วยชื่อ หรือ ประเภท..."
                    onkeyup="filterTable('materialSearch', 'materialsTable', 1, 2)"></div>
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
                        </tr>
                    </thead>
                    <tbody><?php if (empty($materials_list_admin)): ?><tr>
                            <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลวัสดุ</td>
                        </tr><?php else: ?><?php foreach ($materials_list_admin as $mat): ?><tr>
                            <td><?php echo htmlspecialchars($mat['material_id']); ?></td>
                            <td><?php echo htmlspecialchars($mat['product_type']); ?></td>
                            <td><?php echo htmlspecialchars($mat['material_name']); ?></td>
                            <td style="text-align:right;"><?php echo number_format($mat['price_per_unit'], 2); ?></td>
                            <td><?php echo htmlspecialchars($mat['unit']); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-edit"
                                    onclick="openEditModal('material', <?php echo $mat['material_id']; ?>)">แก้ไข</button>
                                <a href="admin.php?action=delete_material&id=<?php echo $mat['material_id']; ?>"
                                    class="btn btn-delete"
                                    onclick="return confirmDelete('วัสดุ', '<?php echo htmlspecialchars(addslashes($mat['material_name'])); ?>')">ลบ</a>
                            </td>
                        </tr><?php endforeach; ?><?php endif; ?></tbody>
                </table>
            </div>
        </div>

        <div class="admin-section" id="options_section">
            <h2>จัดการออปชันเสริม (Options)</h2>
            <form action="admin.php#options_section" method="post">
                <h3>เพิ่มออปชันใหม่</h3>
                <div class="form-group"><label for="add_option_name">ชื่อออปชัน:</label><input type="text"
                        id="add_option_name" name="add_option_name" class="form-control" required></div>
                <div class="form-group"><label for="add_option_price">ราคา(บาท):</label><input type="number"
                        step="0.01" id="add_option_price" name="add_option_price" class="form-control" required></div>
                <div class="btn-group"><button type="submit" name="add_option">เพิ่มออปชัน</button></div>
            </form>
            <h3>รายการออปชันทั้งหมด</h3>
            <div class="search-section"><input type="text" id="optionSearch" class="search-box"
                    placeholder="ค้นหาด้วยชื่อออปชัน..." onkeyup="filterTable('optionSearch', 'optionsTable', 1)"></div>
            <div class="table-responsive">
                <table id="optionsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อออปชัน</th>
                            <th>ราคา(บาท)</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody><?php if (empty($options_list_admin)): ?><tr>
                            <td colspan="4" style="text-align: center;">ยังไม่มีข้อมูลออปชัน</td>
                        </tr><?php else: ?><?php foreach ($options_list_admin as $opt): ?><tr>
                            <td><?php echo htmlspecialchars($opt['option_id']); ?></td>
                            <td><?php echo htmlspecialchars($opt['option_name']); ?></td>
                            <td style="text-align:right;"><?php echo number_format($opt['option_price'], 2); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-edit"
                                    onclick="openEditModal('option', <?php echo $opt['option_id']; ?>)">แก้ไข</button>
                                <a href="admin.php?action=delete_option&id=<?php echo $opt['option_id']; ?>"
                                    class="btn btn-delete"
                                    onclick="return confirmDelete('ออปชัน', '<?php echo htmlspecialchars(addslashes($opt['option_name'])); ?>')">ลบ</a>
                            </td>
                        </tr><?php endforeach; ?><?php endif; ?></tbody>
                </table>
            </div>
        </div>

    </div>
    <div id="editRuleModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editRuleModal')">&times;</span>
            <h3>แก้ไขกฎราคา</h3>
            <form id="editRuleForm">
                <input type="hidden" name="action" value="update_rule">
                <input type="hidden" name="rule_id" id="edit_modal_rule_id">
                <div class="form-group"><label for="edit_modal_rule_name">ชื่อกฎราคา:</label><input type="text"
                        id="edit_modal_rule_name" name="rule_name" class="form-control" required></div>
                <div class="form-group"><label for="edit_modal_rule_value">ราคา(บาท):</label><input type="number" step="0.01"
                        id="edit_modal_rule_value" name="rule_value" class="form-control" required></div>
                <div class="form-group"><label for="edit_modal_rule_unit">หน่วย:</label><input type="text"
                        id="edit_modal_rule_unit" name="rule_unit" class="form-control" required></div>
                <div class="btn-group"><button type="submit">บันทึกการแก้ไข</button><button type="button"
                        class="btn btn-secondary" onclick="closeModal('editRuleModal')">ยกเลิก</button></div>
            </form>
        </div>
    </div>

    <div id="editMaterialModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editMaterialModal')">&times;</span>
            <h3>แก้ไขวัสดุ</h3>
            <form id="editMaterialForm">
                <input type="hidden" name="action" value="update_material">
                <input type="hidden" name="material_id" id="edit_modal_material_id">
                <div class="form-group"><label for="edit_modal_material_type">ประเภทวัสดุ:</label>
                    <select id="edit_modal_material_type" name="material_type" class="form-control" required>
                        <?php foreach ($existing_product_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="edit_modal_material_name">ชื่อวัสดุ:</label><input type="text"
                        id="edit_modal_material_name" name="material_name" class="form-control" required></div>
                <div class="form-group"><label for="edit_modal_material_price">ราคาต่อหน่วย:</label><input type="number"
                        step="0.01" id="edit_modal_material_price" name="material_price" class="form-control" required>
                </div>
                <div class="form-group"><label for="edit_modal_material_unit">หน่วย:</label><input type="text"
                        id="edit_modal_material_unit" name="material_unit" class="form-control" required></div>
                <div class="btn-group"><button type="submit">บันทึกการแก้ไข</button><button type="button"
                        class="btn btn-secondary" onclick="closeModal('editMaterialModal')">ยกเลิก</button></div>
            </form>
        </div>
    </div>

    <div id="editOptionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editOptionModal')">&times;</span>
            <h3>แก้ไขออปชันเสริม</h3>
            <form id="editOptionForm">
                <input type="hidden" name="action" value="update_option">
                <input type="hidden" name="option_id" id="edit_modal_option_id">
                <div class="form-group"><label for="edit_modal_option_name">ชื่อออปชัน:</label><input type="text"
                        id="edit_modal_option_name" name="option_name" class="form-control" required></div>
                <div class="form-group"><label for="edit_modal_option_price">ราคา(บาท):</label><input type="number"
                        step="0.01" id="edit_modal_option_price" name="option_price" class="form-control" required>
                </div>
                <div class="btn-group"><button type="submit">บันทึกการแก้ไข</button><button type="button"
                        class="btn btn-secondary" onclick="closeModal('editOptionModal')">ยกเลิก</button></div>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(type, nameOrId) {
            return confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${type} '${nameOrId}' ? การกระทำนี้ไม่สามารถย้อนกลับได้!`);
        }

        // --- Modal Functions (เหมือนเดิม) ---
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = "none";
        }

        function openEditModal(type, id) {
            let modalId = ''; let formId = '';
            let url = `admin_ajax_data_handler.php?action=get_${type}_data&id=${id}`; //

            if (type === 'rule') { modalId = 'editRuleModal'; formId = 'editRuleForm'; } //
            else if (type === 'material') { modalId = 'editMaterialModal'; formId = 'editMaterialForm'; } //
            else if (type === 'option') { modalId = 'editOptionModal'; formId = 'editOptionForm'; } //
            else { console.error('Unknown modal type:', type); return; }

            const modal = document.getElementById(modalId);
            const form = document.getElementById(formId);
            if (!modal || !form) { console.error('Modal or Form not found for type:', type); return; }

            fetch(url) //
                .then(response => {
                    if (!response.ok) { throw new Error('Network response was not ok ' + response.statusText); } //
                    return response.json(); //
                })
                .then(data => {
                    if (data.success && data.data) { //
                        const itemData = data.data; //
                        if (type === 'rule') { //
                            form.elements['rule_id'].value = itemData.rule_id; //
                            form.elements['rule_name'].value = itemData.rule_name; //
                            form.elements['rule_value'].value = parseFloat(itemData.rule_value).toFixed(2); //
                            form.elements['rule_unit'].value = itemData.rule_unit; //
                        } else if (type === 'material') { //
                            form.elements['material_id'].value = itemData.material_id; //
                            form.elements['material_type'].value = itemData.product_type; //
                            form.elements['material_name'].value = itemData.material_name; //
                            form.elements['material_price'].value = parseFloat(itemData.price_per_unit).toFixed(2); //
                            form.elements['material_unit'].value = itemData.unit; //
                        } else if (type === 'option') { //
                            form.elements['option_id'].value = itemData.option_id; //
                            form.elements['option_name'].value = itemData.option_name; //
                            form.elements['option_price'].value = parseFloat(itemData.option_price).toFixed(2); //
                        }
                        modal.style.display = "block"; //
                    } else {
                        displayGlobalMessage('error', 'ไม่สามารถดึงข้อมูลได้: ' + (data.error || 'ไม่ทราบสาเหตุ')); //
                    }
                })
                .catch(error => {
                    console.error('Error fetching data for modal:', error); //
                    displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' + error.message); //
                });
        }

        document.getElementById('editRuleForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'rules_section', 'editRuleModal'); }); //
        document.getElementById('editMaterialForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'materials_section', 'editMaterialModal'); }); //
        document.getElementById('editOptionForm')?.addEventListener('submit', function(e) { e.preventDefault(); submitModalForm(this, 'admin_ajax_data_handler.php', 'options_section', 'editOptionModal'); }); //

        function submitModalForm(formElement, url, sectionToReload, modalId) {
            const formData = new FormData(formElement); //
            fetch(url, { method: 'POST', body: formData }) //
            .then(response => {
                if (!response.ok) { throw new Error('Network response was not ok ' + response.statusText); } //
                return response.json(); //
            })
            .then(data => {
                if (data.success) { //
                    closeModal(modalId); // ปิด Modal ที่นี่
                    displayGlobalMessage('success', data.message || 'บันทึกข้อมูลเรียบร้อย!'); //
                    setTimeout(() => { window.location.href = 'admin.php?msg=updated#' + sectionToReload; }, 500); //
                } else {
                    displayGlobalMessage('error', 'เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถบันทึกข้อมูลได้'), modalId); //
                }
            })
            .catch(error => {
                console.error('Error submitting modal form:', error); //
                displayGlobalMessage('error', 'เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error.message, modalId); //
            });
        }

        function displayGlobalMessage(type, text, modalIdToKeepOpen = null) { /* ... โค้ดเดิม ... */ } //
        window.onclick = function(event) { /* ... โค้ดเดิม ... */ } //


        // --- Pagination and Filter Functions ---
        let paginatedTables = {};

        function initPagination(tableId, rowsPerPage) {
            const table = document.getElementById(tableId);
            if (!table) { console.error("Table with ID '" + tableId + "' not found for pagination."); return; }
            const tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) { console.error("Tbody not found in table '" + tableId + "'."); return; }

            const allRowsInTbody = Array.from(tbody.getElementsByTagName('tr'));

            paginatedTables[tableId] = {
                originalRows: allRowsInTbody,
                rowsPerPage: parseInt(rowsPerPage, 10) || 5, // Default to 5 if not a valid number
                currentPage: 1,
                paginationControls: null
            };
            repaginate(tableId);
        }

        function showPage(tableId, page) {
            const state = paginatedTables[tableId];
            if (!state) return;

            state.currentPage = page;
            const visibleRows = state.originalRows.filter(row => row.style.display !== 'none' && !row.classList.contains('filtered-out'));


            // Hide all original rows first (respecting those hidden by filter)
            state.originalRows.forEach(row => {
                if (!row.classList.contains('filtered-out')) {
                     row.style.display = 'none';
                }
            });
            
            const start = (page - 1) * state.rowsPerPage;
            const end = start + state.rowsPerPage;

            visibleRows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = ""; 
                }
            });
            updatePaginationControls(tableId);
        }

        function updatePaginationControls(tableId) {
            const state = paginatedTables[tableId];
            if (!state || !state.paginationControls) return;

            const visibleRows = state.originalRows.filter(row => !row.classList.contains('filtered-out'));
            const totalPages = Math.ceil(visibleRows.length / state.rowsPerPage);

            state.paginationControls.innerHTML = ''; 

            if (totalPages <= 1) {
                state.paginationControls.style.display = 'none';
                return;
            }
            state.paginationControls.style.display = 'flex';

            let prevBtn = document.createElement("button");
            prevBtn.innerHTML = "&laquo; ก่อนหน้า";
            prevBtn.type = "button";
            prevBtn.disabled = state.currentPage === 1;
            prevBtn.onclick = function() {
                if (state.currentPage > 1) {
                    showPage(tableId, state.currentPage - 1);
                }
            };
            state.paginationControls.appendChild(prevBtn);

            // Logic to show limited page numbers e.g. 1 ... 5 6 7 ... 10
            const maxPageButtons = 5; // Max number of page buttons to show (excluding prev/next)
            let startPage, endPage;
            if (totalPages <= maxPageButtons) {
                startPage = 1;
                endPage = totalPages;
            } else {
                if (state.currentPage <= Math.ceil(maxPageButtons / 2)) {
                    startPage = 1;
                    endPage = maxPageButtons -1; // Make space for "..." and last page
                } else if (state.currentPage + Math.floor(maxPageButtons / 2) >= totalPages) {
                    startPage = totalPages - maxPageButtons + 2; // Make space for first page and "..."
                    endPage = totalPages;
                } else {
                    startPage = state.currentPage - Math.floor(maxPageButtons / 2) +1;
                    endPage = state.currentPage + Math.floor(maxPageButtons / 2) -1;
                }
            }
            
            if (startPage > 1) {
                let firstPageBtn = document.createElement("button");
                firstPageBtn.innerHTML = 1;
                firstPageBtn.type = "button";
                firstPageBtn.onclick = function() { showPage(tableId, 1); };
                state.paginationControls.appendChild(firstPageBtn);
                if (startPage > 2) {
                    let ellipsis = document.createElement("span");
                    ellipsis.innerHTML = "&hellip;";
                    ellipsis.style.padding = "8px 12px";
                    state.paginationControls.appendChild(ellipsis);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                let pageBtn = document.createElement("button");
                pageBtn.innerHTML = i;
                pageBtn.type = "button";
                if (i === state.currentPage) { pageBtn.classList.add("active"); }
                pageBtn.onclick = function() { showPage(tableId, i); };
                state.paginationControls.appendChild(pageBtn);
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    let ellipsis = document.createElement("span");
                    ellipsis.innerHTML = "&hellip;";
                    ellipsis.style.padding = "8px 12px";
                    state.paginationControls.appendChild(ellipsis);
                }
                let lastPageBtn = document.createElement("button");
                lastPageBtn.innerHTML = totalPages;
                lastPageBtn.type = "button";
                lastPageBtn.onclick = function() { showPage(tableId, totalPages); };
                state.paginationControls.appendChild(lastPageBtn);
            }


            let nextBtn = document.createElement("button");
            nextBtn.innerHTML = "ถัดไป &raquo;";
            nextBtn.type = "button";
            nextBtn.disabled = state.currentPage === totalPages;
            nextBtn.onclick = function() {
                if (state.currentPage < totalPages) {
                    showPage(tableId, state.currentPage + 1);
                }
            };
            state.paginationControls.appendChild(nextBtn);
        }
        
        function repaginate(tableId) {
            const state = paginatedTables[tableId];
            if (!state) return;
            
            const table = document.getElementById(tableId);
            const tbody = table.getElementsByTagName('tbody')[0];

            let paginationDiv = document.getElementById(tableId + 'PaginationContainer');
            if (!paginationDiv) {
                paginationDiv = document.createElement("div");
                paginationDiv.className = "pagination";
                paginationDiv.id = tableId + 'PaginationContainer';
                if (table.parentNode.classList.contains('table-responsive')) {
                    table.parentNode.insertAdjacentElement('afterend', paginationDiv);
                } else {
                    table.insertAdjacentElement('afterend', paginationDiv);
                }
            }
            state.paginationControls = paginationDiv;
            showPage(tableId, 1); 
        }

        function filterTable(inputId, tableId, ...columnIndices) {
            let input = document.getElementById(inputId);
            let filter = input.value.toUpperCase();
            let table = document.getElementById(tableId);
            if (!table) { console.error(`Table with id "${tableId}" not found.`); return; }
            let tbody = table.getElementsByTagName("tbody")[0];
            if (!tbody) { console.error(`Tbody not found in table "${tableId}".`); return; }
            
            const state = paginatedTables[tableId];
            if(!state) { console.error(`Pagination not initialized for table ${tableId}`); return; }

            state.originalRows.forEach(row => {
                let found = false;
                if (row.getElementsByTagName("td").length === 0) {
                    row.style.display = 'none'; 
                    row.classList.add('filtered-out');
                    return;
                }
                for (let colIndex of columnIndices) {
                    let td = row.getElementsByTagName("td")[colIndex];
                    if (td) {
                        let txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true; break;
                        }
                    }
                }
                if (found) {
                    row.style.display = ''; // Will be handled by showPage
                    row.classList.remove('filtered-out');
                } else {
                    row.style.display = 'none';
                    row.classList.add('filtered-out');
                }
            });
            repaginate(tableId);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // กำหนดจำนวนรายการต่อหน้า
            const rowsPerPageRules = 5;
            const rowsPerPageMaterials = 10;
            const rowsPerPageOptions = 5;

            if (document.getElementById('rulesTable')) initPagination('rulesTable', rowsPerPageRules);
            if (document.getElementById('materialsTable')) initPagination('materialsTable', rowsPerPageMaterials);
            if (document.getElementById('optionsTable')) initPagination('optionsTable', rowsPerPageOptions);

            // เรียก filterTable ครั้งแรกเพื่อให้ pagination เริ่มต้นถูกต้อง ถ้ามีการค้นหาค้างอยู่
            if (document.getElementById('ruleSearch') && document.getElementById('ruleSearch').value) filterTable('ruleSearch', 'rulesTable', 1);
            if (document.getElementById('materialSearch') && document.getElementById('materialSearch').value) filterTable('materialSearch', 'materialsTable', 1, 2);
            if (document.getElementById('optionSearch') && document.getElementById('optionSearch').value) filterTable('optionSearch', 'optionsTable', 1);
        });
    </script>
</body>
</html>
</body>

</html>