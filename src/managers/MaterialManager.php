<?php

namespace App\Managers;

use App\Core\DatabaseManager;
use App\Models\Material;

class MaterialManager {
    private \mysqli $connection;

    public function __construct() {
        $this->connection = DatabaseManager::getConnection();
    }

    public function getAllMaterialsOrganized(): array {
        $materials_list_for_letter = [];
        $lightbox_list_for_form = [];
        $sheet_list_for_sticker = [];
        $vinyl_materials_list = [];

        $sql = "SELECT material_id, product_type, material_name, price_per_unit, unit, min_area, max_area 
                FROM materials";
        $result = $this->connection->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $material = new Material(
                    (int)$row['material_id'],
                    $row['product_type'],
                    $row['material_name'],
                    (float)$row['price_per_unit'],
                    $row['unit'],
                    $row['min_area'] ? (float)$row['min_area'] : null,
                    $row['max_area'] ? (float)$row['max_area'] : null
                );

                switch ($row['product_type']) {
                    case 'ตัวอักษรโลหะ':
                        $materials_list_for_letter[] = $material;
                        break;
                    case 'กล่องไฟ':
                        $lightbox_list_for_form[] = $material;
                        break;
                    case 'วัสดุแผ่น':
                        $sheet_list_for_sticker[] = $material;
                        break;
                    case 'ผ้าไวนิล':
                        $vinyl_materials_list[] = $material;
                        break;
                }
            }
        }

        return [
            'materials_list_for_letter' => $materials_list_for_letter,
            'lightbox_list_for_form' => $lightbox_list_for_form,
            'sheet_list_for_sticker' => $sheet_list_for_sticker,
            'vinyl_materials_list' => $vinyl_materials_list
        ];
    }

    public function getMaterialById(int $material_id): ?Material {
        $sql = "SELECT material_id, product_type, material_name, price_per_unit, unit, min_area, max_area 
                FROM materials WHERE material_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $material_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return new Material(
                (int)$row['material_id'],
                $row['product_type'],
                $row['material_name'],
                (float)$row['price_per_unit'],
                $row['unit'],
                $row['min_area'] ? (float)$row['min_area'] : null,
                $row['max_area'] ? (float)$row['max_area'] : null
            );
        }
        return null;
    }

    public function getMaterialsByType(string $product_type): array {
        $sql = "SELECT material_id, product_type, material_name, price_per_unit, unit, min_area, max_area 
                FROM materials WHERE product_type = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $product_type);
        $stmt->execute();
        $result = $stmt->get_result();

        $materials = [];
        while ($row = $result->fetch_assoc()) {
            $materials[] = new Material(
                (int)$row['material_id'],
                $row['product_type'],
                $row['material_name'],
                (float)$row['price_per_unit'],
                $row['unit'],
                $row['min_area'] ? (float)$row['min_area'] : null,
                $row['max_area'] ? (float)$row['max_area'] : null
            );
        }
        return $materials;
    }
}

?> 