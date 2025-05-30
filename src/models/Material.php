<?php

namespace App\Models;

class Material {
    public int $material_id;
    public string $product_type;
    public string $material_name;
    public float $price_per_unit;
    public string $unit;
    public ?float $min_area;
    public ?float $max_area;

    public function __construct(
        int $material_id,
        string $product_type,
        string $material_name,
        float $price_per_unit,
        string $unit,
        ?float $min_area = null,
        ?float $max_area = null
    ) {
        $this->material_id = $material_id;
        $this->product_type = $product_type;
        $this->material_name = $material_name;
        $this->price_per_unit = $price_per_unit;
        $this->unit = $unit;
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

    public function calculatePrice(float $area): float {
        return $area * $this->price_per_unit;
    }
}

?> 