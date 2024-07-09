<?php

namespace App\Services;

use NumberToWords\NumberToWords;
class NumberToWordsService
{
    protected $numberToWords;

    public function __construct()
    {
        $this->numberToWords = new NumberToWords();
    }

    public function convert($number, $language = 'az')
    {
        switch ($language) {
            case 'az':
                $converter = $this->numberToWords->getNumberTransformer('az');
                break;
            case 'en':
            default:
                $converter = $this->numberToWords->getNumberTransformer('en');
                break;
        }

        return $converter->toWords($number);
    }
}
