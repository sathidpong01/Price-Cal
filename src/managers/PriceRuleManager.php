<?php

namespace App\Managers;

use App\Core\DatabaseManager;
use App\Models\PriceRule;

class PriceRuleManager {
    private \mysqli $connection;

    public function __construct() {
        $this->connection = DatabaseManager::getConnection();
    }

    public function getAllPriceRules(): array {
        $price_rules = [];
        $sql = "SELECT rule_name, rule_value FROM price_rules";
        $result = $this->connection->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $price_rules[$row['rule_name']] = $row['rule_value'];
            }
        }
        // It's good practice for manager methods not to close the connection,
        // let the calling script handle that (e.g., at the end of index.php)
        return $price_rules;
    }

    public function getPriceRule(string $rule_name): ?PriceRule {
        $sql = "SELECT rule_name, rule_value FROM price_rules WHERE rule_name = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $rule_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return new PriceRule($row['rule_name'], $row['rule_value']);
        }
        return null;
    }

    public function updatePriceRule(string $rule_name, string $rule_value): bool {
        $sql = "UPDATE price_rules SET rule_value = ? WHERE rule_name = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ss", $rule_value, $rule_name);
        return $stmt->execute();
    }
}

?> 