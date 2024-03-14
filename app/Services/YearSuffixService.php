<?php

namespace App\Services;

class YearSuffixService
{
    public function handle($year)
    {
        $lastDigit = $year % 10;

        if($lastDigit == 0)
        {
            // Remove trailing zeros
            while ($year % 10 === 0 && $year > 0) {
                $year /= 10;
                $lastDigit .= $year % 10;
            }

            $lastDigit = (int)strrev($lastDigit);
        }

        return $this->getSuffix($lastDigit);
    }

    private function getSuffix($digit)
    {
        return match ($digit)
        {
            0,6,40,60,90 => "-cı",
            3,4,100,200,300,400,500,600,700,800,900 => "-cü",
            3,9,10,30 => "-cu",
            default => "-ci",
        };
    }
}
