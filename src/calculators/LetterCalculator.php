<?php

namespace App\Calculators;

use App\Models\Material;
use App\Models\Option;

class LetterCalculator extends BaseCalculator {
    private ?Material $letterMaterial = null;
    private array $selectedOptions = [];
    private ?string $travelType = null;
    private ?float $distanceKm = null;

    /**
     * Set the letter material
     */
    public function setLetterMaterial(?Material $material): void {
        $this->letterMaterial = $material;
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
     * Calculate the total price for the letters
     */
    public function calculate(): void {
        // Reset total price and breakdown
        $this->totalPrice = 0;
        $this->priceBreakdown = [];

        if ($this->letterMaterial === null) {
            throw new \RuntimeException('Letter material must be selected');
        }

        // Calculate price per letter based on height (in inches)
        $pricePerLetter = $this->calculatePricePerLetter();
        $this->addPriceComponent(
            'ราคาตัวอักษร',
            $pricePerLetter,
            "ความสูง {$this->height} นิ้ว × {$this->letterMaterial->price_per_unit} {$this->letterMaterial->unit}"
        );

        // Calculate total for all letters
        $totalLettersPrice = $pricePerLetter * $this->quantity;
        $this->addPriceComponent(
            'ราคารวมตัวอักษร',
            $totalLettersPrice,
            "{$this->quantity} ตัว"
        );

        // Add selected options
        foreach ($this->selectedOptions as $optionId) {
            $option = $this->optionManager->getOptionById($optionId);
            if ($option !== null) {
                $optionPrice = $option->option_price * $this->quantity;
                $this->addPriceComponent($option->option_name, $optionPrice);
            }
        }

        // Add travel cost
        $travelCost = $this->calculateTravelCost();
        if ($travelCost > 0) {
            $this->addPriceComponent('ค่าเดินทาง', $travelCost, $this->getTravelDescription());
        }

        // Round the final price
        $this->totalPrice = $this->roundPrice($this->totalPrice);
    }

    /**
     * Calculate price per letter based on height
     */
    private function calculatePricePerLetter(): float {
        // Get the base price per unit from the material
        $basePrice = $this->letterMaterial->price_per_unit;

        // If the unit is per inch, multiply by height
        if ($this->letterMaterial->unit === 'บาท/นิ้ว') {
            return $basePrice * $this->height;
        }

        // If the unit is per piece, return the base price
        return $basePrice;
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