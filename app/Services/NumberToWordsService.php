<?php

namespace App\Services;

use NumberToWords\NumberToWords;

class NumberToWordsService
{
    protected $numberToWords;

    public function __construct()
    {
        $this->numberToWords = new NumberToWords;
    }

    public function convert($number, $language = 'az')
    {
        $converter = match ($language) {
            'az' => $this->numberToWords->getNumberTransformer('az'),
            default => $this->numberToWords->getNumberTransformer('en'),
        };

        return $converter->toWords($number);
    }
}
