<?php

namespace App\Models;

class PriceRule {
    public string $rule_name;
    public string $rule_value; // Or potentially float/int depending on usage

    public function __construct(string $rule_name, string $rule_value) {
        $this->rule_name = $rule_name;
        $this->rule_value = $rule_value;
    }

    public function getValueAsFloat(): float {
        return (float)$this->rule_value;
    }

    public function getValueAsInt(): int {
        return (int)$this->rule_value;
    }
}

?> 