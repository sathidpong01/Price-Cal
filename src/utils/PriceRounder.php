<?php

namespace App\Utils;

class PriceRounder {
    /**
     * Custom rounding function.
     * Rounds to the nearest 10. For .1, .2, .3 rounds down.
     * For .4, .5, .6, .7, .8, .9 rounds up.
     * Example: 103 -> 100; 104 -> 110; 106 -> 110; 107 -> 110.
     */
    public function round(float $number): int {
        return floor(($number + 6) / 10) * 10;
    }
} 