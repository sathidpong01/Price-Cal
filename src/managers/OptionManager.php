<?php

namespace App\Managers;

use App\Core\DatabaseManager;
use App\Models\Option;

class OptionManager {
    private \mysqli $connection;

    public function __construct() {
        $this->connection = DatabaseManager::getConnection();
    }

    public function getOptionsGroupedByCategory(): array {
        $options_by_category = [
            'ทั่วไป' => [],
            'สติ๊กเกอร์' => [],
            'ผ้าไวนิล' => [],
            'ตัวอักษรโลหะ' => [],
            'กล่องไฟ' => []
        ];

        $sql = "SELECT option_id, option_name, option_price, category, min_area, max_area FROM options";
        $result = $this->connection->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $option = new Option(
                    (int)$row['option_id'],
                    $row['option_name'],
                    (float)$row['option_price'],
                    $row['category'],
                    $row['min_area'] ? (float)$row['min_area'] : null,
                    $row['max_area'] ? (float)$row['max_area'] : null
                );
                
                $category_key = $row['category'] ?? 'ทั่วไป';
                if (array_key_exists($category_key, $options_by_category)) {
                    $options_by_category[$category_key][] = $option;
                } else {
                    $options_by_category['ทั่วไป'][] = $option;
                }
            }
        }
        return $options_by_category;
    }

    public function getOptionById(int $option_id): ?Option {
        $sql = "SELECT option_id, option_name, option_price, category, min_area, max_area 
                FROM options WHERE option_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $option_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return new Option(
                (int)$row['option_id'],
                $row['option_name'],
                (float)$row['option_price'],
                $row['category'],
                $row['min_area'] ? (float)$row['min_area'] : null,
                $row['max_area'] ? (float)$row['max_area'] : null
            );
        }
        return null;
    }

    public function getOptionsByCategory(string $category): array {
        $sql = "SELECT option_id, option_name, option_price, category, min_area, max_area 
                FROM options WHERE category = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();

        $options = [];
        while ($row = $result->fetch_assoc()) {
            $options[] = new Option(
                (int)$row['option_id'],
                $row['option_name'],
                (float)$row['option_price'],
                $row['category'],
                $row['min_area'] ? (float)$row['min_area'] : null,
                $row['max_area'] ? (float)$row['max_area'] : null
            );
        }
        return $options;
    }
}

?> 