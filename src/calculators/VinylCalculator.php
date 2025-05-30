<?php

namespace App\Calculators;

use App\Models\Material;
use App\Models\Option;

class VinylCalculator extends BaseCalculator {
    private ?Material $vinylMaterial = null;
    private array $selectedOptions = [];
    private ?string $travelType = null;
    private ?float $distanceKm = null;

    /**
     * Set the vinyl material
     */
    public function setVinylMaterial(?Material $material): void {
        $this->vinylMaterial = $material;
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
     * Calculate the total price for the vinyl
     */
    public function calculate(): void {
        // Reset total price and breakdown
        $this->totalPrice = 0;
        $this->priceBreakdown = [];

        if ($this->vinylMaterial === null) {
            throw new \RuntimeException('Vinyl material must be selected');
        }

        // Calculate area in square meters
        $areaSqm = ($this->width * $this->height) / 10000; // Convert cm² to m²

        // Calculate material price
        $materialPrice = $this->vinylMaterial->calculatePrice($areaSqm);
        $this->addPriceComponent(
            'ราคาวัสดุ',
            $materialPrice,
            "{$areaSqm} ตร.ม. × {$this->vinylMaterial->price_per_unit} บาท/ตร.ม."
        );

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