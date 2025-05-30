<?php

namespace App\Calculators;

use App\Models\Material;
use App\Models\Option;

class StickerCalculator extends BaseCalculator {
    private ?Material $sheetMaterial = null;
    private array $selectedOptions = [];
    private ?string $travelType = null;
    private ?float $distanceKm = null;

    /**
     * Set the sheet material for the sticker
     */
    public function setSheetMaterial(?Material $material): void {
        $this->sheetMaterial = $material;
    }

    /**
     * Set the selected options
     */
    public function setSelectedOptions(array $optionIds): void {
        $this->selectedOptions = $optionIds;
    }

    /**
     * Set travel information
     */
    public function setTravelInfo(?string $type, ?float $distanceKm): void {
        $this->travelType = $type;
        $this->distanceKm = $distanceKm;
    }

    /**
     * Calculate the total price for the sticker
     */
    public function calculate(): void {
        // Reset total price and breakdown
        $this->totalPrice = 0;
        $this->priceBreakdown = [];

        // Get base price per square meter from price rules
        $basePricePerSqm = (float)($this->priceRuleManager->getPriceRule('Sticker Price Per SQM')?->rule_value ?? 500);

        // Calculate area in square meters
        $areaSqm = ($this->width * $this->height) / 10000; // Convert cm² to m²

        // Calculate base price
        $basePrice = $areaSqm * $basePricePerSqm;
        $this->addPriceComponent('ราคาพื้นฐาน', $basePrice, "{$areaSqm} ตร.ม. × {$basePricePerSqm} บาท/ตร.ม.");

        // Add sheet material cost if selected
        if ($this->sheetMaterial !== null) {
            $sheetPrice = $this->sheetMaterial->calculatePrice($areaSqm);
            $this->addPriceComponent(
                'วัสดุแผ่นเสริม',
                $sheetPrice,
                "{$areaSqm} ตร.ม. × {$this->sheetMaterial->price_per_unit} บาท/ตร.ม."
            );
        }

        // Add selected options
        foreach ($this->selectedOptions as $optionId) {
            $option = $this->optionManager->getOptionById($optionId);
            if ($option !== null && $option->isApplicableForArea($areaSqm)) {
                $this->addPriceComponent($option->option_name, $option->option_price);
            }
        }

        // Add travel cost
        $travelCost = $this->calculateTravelCost();
        if ($travelCost > 0) {
            $this->addPriceComponent('ค่าเดินทาง', $travelCost, $this->getTravelDescription());
        }

        // Apply quantity
        $this->totalPrice *= $this->quantity;
        foreach ($this->priceBreakdown as &$component) {
            $component['price'] *= $this->quantity;
        }

        // Round the final price
        $this->totalPrice = $this->roundPrice($this->totalPrice);
    }

    /**
     * Calculate travel cost based on type and distance
     */
    private function calculateTravelCost(): float {
        if ($this->travelType === 'in_city') {
            return (float)($this->priceRuleManager->getPriceRule('Travel Cost In City')?->rule_value ?? 500);
        } elseif ($this->travelType === 'out_city' && $this->distanceKm !== null && $this->distanceKm > 0) {
            $costPerKm = (float)($this->priceRuleManager->getPriceRule('Travel Cost Per KM')?->rule_value ?? 10);
            return $this->distanceKm * $costPerKm;
        }
        return 0;
    }

    /**
     * Get travel cost description
     */
    private function getTravelDescription(): string {
        if ($this->travelType === 'in_city') {
            return 'ในเมือง';
        } elseif ($this->travelType === 'out_city' && $this->distanceKm !== null) {
            return "นอกเมือง ({$this->distanceKm} กม.)";
        }
        return 'ไม่รวมค่าเดินทาง';
    }
} 