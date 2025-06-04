<head>
    <link rel="stylesheet" href="css/modals.css">
</head>

<div id="editRuleModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editRuleModal')">&times;</span>
        <h2>แก้ไขกฎราคา:</h2>
        <form id="editRuleForm" method="post" action="admin_ajax_data_handler.php">
            <input type="hidden" name="action" value="update_rule">
            <input type="hidden" id="edit_rule_id" name="rule_id">
            <div class="form-group">
                <label for="edit_rule_name">ชื่อกฎ:</label>
                <input type="text" id="edit_rule_name" name="rule_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_rule_value">ค่า:</label>
                <input type="number" step="0.01" id="edit_rule_value" name="rule_value" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_rule_unit">หน่วย:</label>
                <input type="text" id="edit_rule_unit" name="rule_unit" class="form-control" required>
            </div>
            <div class="btn-group">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<div id="editMaterialModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editMaterialModal')">&times;</span>
        <h2>แก้ไขวัสดุ:</h2>
        <form id="editMaterialForm" method="post" action="admin_ajax_data_handler.php">
            <input type="hidden" name="action" value="update_material">
            <input type="hidden" id="edit_material_id" name="material_id">
            <div class="form-group">
                <label for="edit_material_type">ประเภทวัสดุ:</label>
                <select id="edit_material_type" name="material_type" class="form-control" required>
                    <?php foreach (array_unique($existing_product_types) as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_material_name">ชื่อวัสดุ:</label>
                <input type="text" id="edit_material_name" name="material_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_material_price">ราคาต่อหน่วย:</label>
                <input type="number" step="0.01" id="edit_material_price" name="material_price" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_material_unit">หน่วย:</label>
                <input type="text" id="edit_material_unit" name="material_unit" class="form-control" required>
            </div>
            <div class="btn-group">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<div id="editOptionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editOptionModal')">&times;</span>
        <h2>แก้ไขออปชันเสริม:</h2>
        <form id="editOptionForm" method="post" action="admin_ajax_data_handler.php">
            <input type="hidden" name="action" value="update_option">
            <input type="hidden" id="edit_option_id" name="option_id">
            <div class="form-group">
                <label for="edit_option_name">ชื่อออปชัน:</label>
                <input type="text" id="edit_option_name" name="option_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_option_price">ราคา (บาท):</label>
                <input type="number" step="0.01" id="edit_option_price" name="option_price" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_option_category">หมวดหมู่:</label>
                <select id="edit_option_category" name="option_category" class="form-control" required>
                     <?php foreach ($option_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="btn-group">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<div id="editStockModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editStockModal')">&times;</span>
        <h2>แก้ไขสินค้าในสต็อก:</h2>
        <form id="editStockForm" method="post" action="admin_ajax_data_handler.php">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" id="edit_stock_id" name="stock_id">
            <div class="form-group">
                <label for="edit_stock_product_name">ชื่อสินค้า:</label>
                <input type="text" id="edit_stock_product_name" name="product_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_stock_product_type">ประเภทสินค้า:</label>
                <input type="text" id="edit_stock_product_type" name="product_type" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_stock_quantity">จำนวน:</label>
                <input type="number" step="1" id="edit_stock_quantity" name="quantity" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_stock_unit">หน่วย:</label>
                <input type="text" id="edit_stock_unit" name="unit" class="form-control" required>
            </div>
            <div class="btn-group">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>