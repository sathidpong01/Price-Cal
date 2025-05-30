<?php

namespace App\Calculators;

use App\Managers\PriceRuleManager;
use App\Managers\OptionManager;
use App\Managers\MaterialManager;
use App\Utils\PriceRounder;

abstract class BaseCalculator {
    protected PriceRuleManager $priceRuleManager;
    protected OptionManager $optionManager;
    protected MaterialManager $materialManager;
    protected PriceRounder $priceRounder;
    
    protected array $options = [];
    protected array $materials = [];
    protected array $priceRules = [];
    
    protected float $width = 0;
    protected float $height = 0;
    protected float $quantity = 1;
    protected float $totalPrice = 0;
    protected array $priceBreakdown = [];
    
    public function __construct(
        PriceRuleManager $priceRuleManager,
        OptionManager $optionManager,
        MaterialManager $materialManager,
        PriceRounder $priceRounder
    ) {
        $this->priceRuleManager = $priceRuleManager;
        $this->optionManager = $optionManager;
        $this->materialManager = $materialManager;
        $this->priceRounder = $priceRounder;
    }
    
    /**
     * Set the dimensions and quantity for the calculation
     */
    public function setDimensions(float $width, float $height, float $quantity = 1): void {
        $this->width = $width;
        $this->height = $height;
        $this->quantity = $quantity;
    }
    
    /**
     * Get the total price
     */
    public function getTotalPrice(): float {
        return $this->totalPrice;
    }
    
    /**
     * Get the price breakdown
     */
    public function getPriceBreakdown(): array {
        return $this->priceBreakdown;
    }
    
    /**
     * Calculate the total price
     * This method should be implemented by specific calculators
     */
    abstract public function calculate(): void;
    
    /**
     * Calculate the area in square feet
     */
    protected function calculateArea(): float {
        return ($this->width * $this->height) / 144; // Convert square inches to square feet
    }
    
    /**
     * Calculate the perimeter in feet
     */
    protected function calculatePerimeter(): float {
        return (($this->width * 2) + ($this->height * 2)) / 12; // Convert inches to feet
    }
    
    /**
     * Add a price component to the breakdown
     */
    protected function addPriceComponent(string $name, float $price, string $description = ''): void {
        $this->priceBreakdown[] = [
            'name' => $name,
            'price' => $price,
            'description' => $description
        ];
        $this->totalPrice += $price;
    }
    
    /**
     * Get applicable price rules for the current dimensions
     */
    protected function getApplicablePriceRules(): array {
        $area = $this->calculateArea();
        return array_filter($this->priceRules, function($rule) use ($area) {
            return $area >= $rule['min_area'] && $area <= $rule['max_area'];
        });
    }
    
    /**
     * Get applicable options for the current dimensions
     */
    protected function getApplicableOptions(): array {
        $area = $this->calculateArea();
        return array_filter($this->options, function($option) use ($area) {
            return $option->isApplicableForArea($area);
        });
    }
    
    /**
     * Get applicable materials for the current dimensions
     */
    protected function getApplicableMaterials(): array {
        $area = $this->calculateArea();
        return array_filter($this->materials, function($material) use ($area) {
            return $material->isApplicableForArea($area);
        });
    }
    
    /**
     * Round a price according to the rounding rules
     */
    protected function roundPrice(float $price): int {
        return $this->priceRounder->round($price);
    }
}

?> 