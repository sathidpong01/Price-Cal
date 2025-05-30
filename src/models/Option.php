<?php

namespace App\Models;

class Option {
    public int $option_id;
    public string $option_name;
    public float $option_price;
    public string $category;
    public ?float $min_area;
    public ?float $max_area;

    public function __construct(
        int $option_id,
        string $option_name,
        float $option_price,
        string $category,
        ?float $min_area = null,
        ?float $max_area = null
    ) {
        $this->option_id = $option_id;
        $this->option_name = $option_name;
        $this->option_price = $option_price;
        $this->category = $category;
        $this->min_area = $min_area;
        $this->max_area = $max_area;
    }

    public function isApplicableForArea(float $area): bool {
        if ($this->min_area === null && $this->max_area === null) {
            return true;
        }
        if ($this->min_area !== null && $area < $this->min_area) {
            return false;
        }
        if ($this->max_area !== null && $area > $this->max_area) {
            return false;
        }
        return true;
    }
}

?> 